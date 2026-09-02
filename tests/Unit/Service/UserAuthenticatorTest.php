<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Platform\User\Application\Service\PasswordHasher;
use Semitexa\Platform\User\Application\Service\UserAuthenticator;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\AuthFailure;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

final class UserAuthenticatorTest extends TestCase
{
    private const PASSWORD = 'correct-horse-battery';

    #[Test]
    public function the_right_password_is_granted_and_the_sign_in_is_recorded(): void
    {
        $repo = self::repository(self::user());
        $auth = self::authenticator($repo);

        $attempt = $auth->attempt('admin@example.test', self::PASSWORD, 'regmus');

        self::assertTrue($attempt->success);
        self::assertNotNull($attempt->user);
        self::assertNotNull($repo->updated?->getLastLoginAt());
        self::assertSame(0, $repo->updated?->getFailedAttempts());
    }

    #[Test]
    public function an_unknown_address_and_a_wrong_password_are_reported_apart_but_shown_alike(): void
    {
        $missing = self::authenticator(self::repository(null))
            ->attempt('nobody@example.test', self::PASSWORD);
        $wrong = self::authenticator(self::repository(self::user()))
            ->attempt('admin@example.test', 'not-the-password');

        // The distinction is for the log; neither is safe to put in front of an
        // anonymous visitor, or the form becomes an account-enumeration oracle.
        self::assertSame(AuthFailure::NoSuchUser, $missing->failure);
        self::assertSame(AuthFailure::WrongPassword, $wrong->failure);
        self::assertFalse($missing->failure->isSafeToDisclose());
        self::assertFalse($wrong->failure->isSafeToDisclose());
    }

    #[Test]
    public function repeated_failures_lock_the_account_and_the_lock_then_wins_over_the_password(): void
    {
        $repo = self::repository(self::user(failedAttempts: 2));
        $auth = self::authenticator($repo);

        $third = $auth->attempt('admin@example.test', 'wrong');

        self::assertSame(AuthFailure::Locked, $third->failure);
        self::assertNotNull($third->lockedUntil);
        self::assertTrue($repo->updated?->isLocked());

        // The correct password does not get you past a live lock.
        $locked = self::authenticator(self::repository($repo->updated))
            ->attempt('admin@example.test', self::PASSWORD);

        self::assertFalse($locked->success);
        self::assertSame(AuthFailure::Locked, $locked->failure);
    }

    #[Test]
    public function a_disabled_account_is_refused_before_the_password_is_even_compared(): void
    {
        $attempt = self::authenticator(self::repository(self::user(status: UserStatus::Disabled)))
            ->attempt('admin@example.test', self::PASSWORD);

        self::assertFalse($attempt->success);
        self::assertSame(AuthFailure::Disabled, $attempt->failure);
    }

    #[Test]
    public function a_failed_bookkeeping_write_does_not_deny_a_valid_sign_in(): void
    {
        $repo = self::repository(self::user());
        $repo->throwOnUpdate = true;

        $attempt = self::authenticator($repo)->attempt('admin@example.test', self::PASSWORD);

        self::assertTrue($attempt->success);
    }

    private static function user(
        UserStatus $status = UserStatus::Active,
        int $failedAttempts = 0,
    ): PlatformUser {
        return new PlatformUser(
            id: 'u-1',
            email: 'admin@example.test',
            passwordHash: password_hash(self::PASSWORD, PASSWORD_BCRYPT, ['cost' => 4]),
            tenantId: 'regmus',
            role: UserRole::Admin,
            status: $status,
            failedAttempts: $failedAttempts,
        );
    }

    private static function repository(?PlatformUser $user): PlatformUserRepositoryInterface
    {
        return new class ($user) implements PlatformUserRepositoryInterface {
            public ?PlatformUser $updated = null;
            public bool $throwOnUpdate = false;

            public function __construct(private ?PlatformUser $user) {}

            public function findById(string $id): ?PlatformUser
            {
                return $this->user;
            }

            public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser
            {
                return $this->user;
            }

            public function findAll(?string $tenantId = null, bool $includeDisabled = false): array
            {
                return $this->user === null ? [] : [$this->user];
            }

            public function save(PlatformUser $user): void {}

            public function update(PlatformUser $user): void
            {
                if ($this->throwOnUpdate) {
                    throw new \RuntimeException('database is away');
                }

                $this->updated = $user;
            }

            public function delete(string $id): void {}
        };
    }

    private static function authenticator(PlatformUserRepositoryInterface $users): UserAuthenticator
    {
        $auth = new UserAuthenticator();

        self::set($auth, 'users', $users);
        self::set($auth, 'hasher', new PasswordHasher());
        self::set($auth, 'maxAttempts', 3);
        self::set($auth, 'lockSeconds', 900);

        return $auth;
    }

    private static function set(object $target, string $property, mixed $value): void
    {
        $ref = new \ReflectionProperty($target, $property);
        $ref->setValue($target, $value);
    }
}
