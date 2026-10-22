<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Log\LoggerInterface;
use Semitexa\Orm\Domain\Model\ConnectionConfig;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Service\PasswordHasher;
use Semitexa\Platform\User\Application\Service\PasswordResetService;
use Semitexa\Platform\User\Application\Service\PasswordResetTokenStore;
use Semitexa\Platform\User\Application\Service\UnconfiguredPasswordResetMailer;
use Semitexa\Platform\User\Domain\Contract\PasswordResetMailerInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\PasswordResetOutcome;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * The reset backend behind platform.forgot-password / platform.reset-password
 * (tk-parity-password-reset), on a real token table in in-memory SQLite.
 */
final class PasswordResetServiceTest extends TestCase
{
    private const OLD = 'correct-horse-battery';
    private const NEW = 'a-brand-new-passphrase';

    private OrmManager $orm;
    private PasswordResetTokenStore $tokens;
    /** @var list<array{user: PlatformUser, token: string, expiresAt: \DateTimeImmutable}> */
    private array $sent = [];
    private \DateTimeImmutable $now;
    /** @var list<array{string, array<string, mixed>}> */
    private array $errors = [];

    protected function setUp(): void
    {
        $this->orm = new OrmManager(config: new ConnectionConfig(driver: 'sqlite', sqliteMemory: true));
        $this->orm->getAdapter()->execute(
            'CREATE TABLE platform_user_password_reset (
                token_hash CHAR(64) PRIMARY KEY, user_id VARCHAR(36) NOT NULL, tenant_id VARCHAR(64) NOT NULL,
                created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, used_at DATETIME NULL,
                credential_version INT NOT NULL DEFAULT 0
            )',
        );
        $this->tokens = new PasswordResetTokenStore();
        self::set($this->tokens, 'orm', $this->orm);
        $this->now = new \DateTimeImmutable('2026-10-20 12:00:00');
    }

    protected function tearDown(): void
    {
        $this->orm->shutdown();
    }

    #[Test]
    public function a_link_resets_the_password_once_and_clears_the_lockout(): void
    {
        $users = self::users(self::user(failedAttempts: 5, lockedUntil: new \DateTimeImmutable('2026-10-20 13:00:00')));
        $service = $this->service($users);

        $service->request('Admin@Example.test ', null, $this->now);
        self::assertCount(1, $this->sent);
        $token = $this->sent[0]['token'];
        self::assertSame('2026-10-20 13:00:00', $this->sent[0]['expiresAt']->format('Y-m-d H:i:s'));
        self::assertGreaterThanOrEqual(43, strlen($token));
        $rows = $this->rowsWhere('1 = 1');
        self::assertCount(1, $rows);
        self::assertSame(hash('sha256', $token), $rows[0]['token_hash']);
        self::assertStringNotContainsString($token, (string) json_encode($rows), 'the table holds the hash, never the token');

        $result = $service->reset($token, self::NEW, $this->now->modify('+10 minutes'));

        self::assertTrue($result->succeeded());
        $updated = $users->findById('u-1');
        self::assertNotNull($updated);
        self::assertTrue($updated->verifyPassword(self::NEW));
        self::assertFalse($updated->verifyPassword(self::OLD));
        self::assertSame(0, $updated->getFailedAttempts());
        self::assertFalse($updated->isLocked($this->now));

        self::assertSame(
            PasswordResetOutcome::InvalidToken,
            $service->reset($token, 'yet-another-passphrase', $this->now->modify('+11 minutes'))->outcome,
            'a link works once',
        );
    }

    #[Test]
    public function an_expired_or_unknown_link_resets_nothing(): void
    {
        $users = self::users(self::user());
        $service = $this->service($users);
        $service->request('admin@example.test', null, $this->now);
        $token = $this->sent[0]['token'];

        self::assertSame(PasswordResetOutcome::InvalidToken, $service->reset($token, self::NEW, $this->now->modify('+1 hour'))->outcome);
        self::assertSame(PasswordResetOutcome::InvalidToken, $service->reset('not-a-token', self::NEW, $this->now)->outcome);
        self::assertSame(PasswordResetOutcome::InvalidToken, $service->reset('', self::NEW, $this->now)->outcome);
        self::assertTrue($users->findById('u-1')?->verifyPassword(self::OLD));
    }

    #[Test]
    public function a_refused_password_leaves_the_link_working(): void
    {
        $users = self::users(self::user());
        $service = $this->service($users);
        $service->request('admin@example.test', null, $this->now);
        $token = $this->sent[0]['token'];

        $refused = $service->reset($token, 'short', $this->now);

        self::assertSame(PasswordResetOutcome::PasswordRejected, $refused->outcome);
        self::assertNotNull($refused->passwordProblem);
        self::assertTrue($service->reset($token, self::NEW, $this->now)->succeeded());
    }

    #[Test]
    public function a_reset_uses_up_every_other_link_of_the_account(): void
    {
        $users = self::users(self::user());
        $service = $this->service($users);
        $service->request('admin@example.test', null, $this->now);
        $service->request('admin@example.test', null, $this->now->modify('+1 minute'));
        [$first, $second] = [$this->sent[0]['token'], $this->sent[1]['token']];

        self::assertTrue($service->reset($second, self::NEW, $this->now->modify('+2 minutes'))->succeeded());
        self::assertSame(PasswordResetOutcome::InvalidToken, $service->reset($first, 'someone-elses-choice', $this->now->modify('+3 minutes'))->outcome);
        self::assertTrue($users->findById('u-1')?->verifyPassword(self::NEW));
    }

    /** Only the reset flow revoked links: an attacker's live link outlived a change meant to lock them out. */
    #[Test]
    public function a_password_set_any_other_way_voids_the_links_sent_before(): void
    {
        $users = self::users(self::user());
        $service = $this->service($users);
        $service->request('admin@example.test', null, $this->now);
        $user = $users->findById('u-1');
        self::assertNotNull($user);
        $users->update($user->withPasswordHash((new PasswordHasher())->hash('changed-in-the-console')));

        self::assertSame(PasswordResetOutcome::InvalidToken, $service->reset($this->sent[0]['token'], self::NEW, $this->now->modify('+1 minute'))->outcome);
    }

    #[Test]
    public function an_unknown_or_disabled_address_gets_no_link_and_the_same_silence(): void
    {
        $this->service(self::users(null))->request('nobody@example.test', null, $this->now);
        $this->service(self::users(self::user(status: UserStatus::Disabled)))->request('admin@example.test', null, $this->now);

        self::assertSame([], $this->sent);
        self::assertSame([], $this->rowsWhere('1 = 1'));
    }

    #[Test]
    public function an_account_gets_at_most_the_limit_of_links_per_window(): void
    {
        $service = $this->service(self::users(self::user()));

        for ($i = 0; $i < 5; $i++) {
            $service->request('admin@example.test', null, $this->now->modify("+{$i} minutes"));
        }
        self::assertCount(3, $this->sent, 'three per fifteen minutes by default');

        $service->request('admin@example.test', null, $this->now->modify('+16 minutes'));
        self::assertCount(4, $this->sent, 'the window moves on');
    }

    #[Test]
    public function a_mailer_that_fails_answers_like_an_unknown_address_and_is_logged(): void
    {
        $mailer = new class () implements PasswordResetMailerInterface {
            public string $token = '';

            public function sendResetLink(PlatformUser $user, string $token, \DateTimeImmutable $expiresAt): void
            {
                $this->token = $token;
                throw new \RuntimeException('SMTP is down');
            }
        };

        // A 500 for real addresses and silence for unknown ones would say
        // which addresses have accounts.
        $this->service(self::users(self::user()), $mailer)->request('admin@example.test', null, $this->now);

        self::assertCount(1, $this->errors);
        self::assertStringContainsString('SMTP is down', (string) json_encode($this->errors));
        self::assertNotSame('', $mailer->token);
        self::assertStringNotContainsString($mailer->token, (string) json_encode($this->errors), 'the log never holds the token');
    }

    #[Test]
    public function without_a_mailer_every_request_is_refused_alike(): void
    {
        $service = $this->service(self::users(null), new UnconfiguredPasswordResetMailer());

        // The same refusal for an address with no account: answering only for
        // real ones would tell which addresses exist.
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(PasswordResetMailerInterface::class);

        $service->request('nobody@example.test', null, $this->now);
    }

    /** @return list<array<string, mixed>> */
    private function rowsWhere(string $condition): array
    {
        return $this->orm->getAdapter()->query('SELECT * FROM platform_user_password_reset WHERE ' . $condition)->fetchAll();
    }

    private function service(PlatformUserRepositoryInterface $users, ?PasswordResetMailerInterface $mailer = null): PasswordResetService
    {
        $service = new PasswordResetService();
        self::set($service, 'users', $users);
        self::set($service, 'hasher', self::hasher());
        self::set($service, 'tokens', $this->tokens);
        self::set($service, 'mailer', $mailer ?? new class ($this->sent) implements PasswordResetMailerInterface {
            /** @param list<array{user: PlatformUser, token: string, expiresAt: \DateTimeImmutable}> $sent */
            public function __construct(private array &$sent) {}

            public function sendResetLink(PlatformUser $user, string $token, \DateTimeImmutable $expiresAt): void
            {
                $this->sent[] = ['user' => $user, 'token' => $token, 'expiresAt' => $expiresAt];
            }
        });
        self::set($service, 'logger', new class ($this->errors) implements LoggerInterface {
            /** @param list<array{string, array<string, mixed>}> $errors */
            public function __construct(private array &$errors) {}

            public function error(string $message, array $context = []): void
            {
                $this->errors[] = [$message, $context];
            }

            public function critical(string $message, array $context = []): void
            {
                $this->errors[] = [$message, $context];
            }

            public function warning(string $message, array $context = []): void {}

            public function info(string $message, array $context = []): void {}

            public function notice(string $message, array $context = []): void {}

            public function debug(string $message, array $context = []): void {}
        });
        self::set($service, 'ttlSeconds', 3600);
        self::set($service, 'maxRequests', 3);
        self::set($service, 'windowSeconds', 900);

        return $service;
    }

    private static function hasher(): PasswordHasher
    {
        $hasher = new PasswordHasher();
        // The cheapest Argon2 cost: the test is about the links, not the hash.
        self::set($hasher, 'memoryKb', 8);
        self::set($hasher, 'timeCost', 1);

        return $hasher;
    }

    private static function user(
        UserStatus $status = UserStatus::Active,
        int $failedAttempts = 0,
        ?\DateTimeImmutable $lockedUntil = null,
    ): PlatformUser {
        return new PlatformUser(
            id: 'u-1',
            email: 'admin@example.test',
            passwordHash: password_hash(self::OLD, PASSWORD_BCRYPT, ['cost' => 4]),
            tenantId: 'regmus',
            status: $status,
            failedAttempts: $failedAttempts,
            lockedUntil: $lockedUntil,
        );
    }

    private static function users(?PlatformUser $user): PlatformUserRepositoryInterface
    {
        return new class ($user) implements PlatformUserRepositoryInterface {
            public function __construct(private ?PlatformUser $user) {}

            public function findById(string $id): ?PlatformUser
            {
                return $this->user?->getId() === $id ? $this->user : null;
            }

            public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser
            {
                return $this->user?->getEmail() === $email ? $this->user : null;
            }

            public function findAll(?string $tenantId = null, bool $includeDisabled = false): array
            {
                return $this->user === null ? [] : [$this->user];
            }

            public function save(PlatformUser $user): void
            {
                $this->user = $user;
            }

            public function update(PlatformUser $user): void
            {
                $this->user = $user;
            }

            public function delete(string $id): void
            {
                $this->user = null;
            }
        };
    }

    private static function set(object $target, string $property, mixed $value): void
    {
        (new \ReflectionProperty($target, $property))->setValue($target, $value);
    }
}
