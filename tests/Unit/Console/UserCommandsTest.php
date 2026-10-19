<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Console;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Platform\User\Application\Console\Command\UserAddCommand;
use Semitexa\Platform\User\Application\Console\Command\UserDisableCommand;
use Semitexa\Platform\User\Application\Console\Command\UserListCommand;
use Semitexa\Platform\User\Application\Console\Command\UserPasswordCommand;
use Semitexa\Platform\User\Application\Service\PasswordHasher;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The account commands an operator runs on a fresh install — user:add,
 * user:password, user:disable, user:list — through Symfony's own tester
 * against an in-memory store that keeps the repository's contract
 * (tk-parity-proof-gaps). Until this test none of them had run once.
 */
final class UserCommandsTest extends TestCase
{
    private CommandUsers $users;

    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->users = new CommandUsers();
        $this->hasher = new PasswordHasher();
        // The cheapest cost the hasher accepts: the suite is not the place to pay for 64 MiB.
        (new \ReflectionProperty(PasswordHasher::class, 'memoryKb'))->setValue($this->hasher, 8192);
        (new \ReflectionProperty(PasswordHasher::class, 'timeCost'))->setValue($this->hasher, 2);
    }

    #[Test]
    public function user_add_creates_an_account_that_can_sign_in(): void
    {
        $add = $this->runCommand(new UserAddCommand(), ['--email' => ' Ada@Example.TEST ', '--password' => 'correct horse', '--tenant' => 't-1', '--name' => 'Ada']);

        self::assertSame(Command::SUCCESS, $add->getStatusCode(), $add->getDisplay());
        $user = $this->users->findByEmail('ada@example.test', 't-1');
        self::assertNotNull($user, 'the address is stored normalised');
        self::assertSame(['Ada', 't-1', UserRole::Admin, UserStatus::Active], [$user->getDisplayName(), $user->getTenantId(), $user->getRole(), $user->getStatus()]);
        self::assertTrue($user->verifyPassword('correct horse'));
        self::assertStringNotContainsString('correct horse', $user->getPasswordHash());
        self::assertStringContainsString('Created ada@example.test as admin of t-1', $add->getDisplay());
    }

    #[Test]
    public function user_add_refuses_what_would_make_a_broken_account(): void
    {
        $cases = [
            'no email' => [['--password' => 'correct horse', '--tenant' => 't-1'], Command::INVALID, 'valid --email'],
            'bad role' => [['--email' => 'a@example.test', '--password' => 'correct horse', '--tenant' => 't-1', '--role' => 'root'], Command::INVALID, '--role must be one of'],
            'admin without a tenant' => [['--email' => 'a@example.test', '--password' => 'correct horse'], Command::INVALID, '--tenant is required'],
            'short password' => [['--email' => 'a@example.test', '--password' => 'short', '--tenant' => 't-1'], Command::INVALID, 'at least 10 characters'],
        ];
        foreach ($cases as $case => [$options, $status, $message]) {
            $tester = $this->runCommand(new UserAddCommand(), $options);
            self::assertSame($status, $tester->getStatusCode(), $case);
            self::assertStringContainsString($message, $tester->getDisplay(), $case);
        }
        self::assertSame([], $this->users->findAll(null, true), 'nothing refused was stored');
    }

    #[Test]
    public function user_add_refuses_an_address_the_tenant_already_has_but_not_another_tenants(): void
    {
        $this->runCommand(new UserAddCommand(), ['--email' => 'ada@example.test', '--password' => 'correct horse', '--tenant' => 't-1']);

        $again = $this->runCommand(new UserAddCommand(), ['--email' => 'ADA@example.test', '--password' => 'another horse', '--tenant' => 't-1']);
        $elsewhere = $this->runCommand(new UserAddCommand(), ['--email' => 'ada@example.test', '--password' => 'another horse', '--tenant' => 't-2']);

        self::assertSame(Command::FAILURE, $again->getStatusCode());
        self::assertStringContainsString('already exists for tenant t-1', $again->getDisplay());
        self::assertSame(Command::SUCCESS, $elsewhere->getStatusCode(), 'one person may administer two sites');
        self::assertCount(2, $this->users->findAll(null, true));
    }

    #[Test]
    public function an_owner_needs_no_tenant(): void
    {
        $owner = $this->runCommand(new UserAddCommand(), ['--email' => 'root@example.test', '--password' => 'correct horse', '--role' => 'owner']);

        self::assertSame(Command::SUCCESS, $owner->getStatusCode(), $owner->getDisplay());
        self::assertSame(UserRole::Owner, $this->account('root@example.test')->getRole());
    }

    #[Test]
    public function user_password_replaces_the_password_and_clears_a_lockout(): void
    {
        $this->users->save((new PlatformUser('u-1', 'ada@example.test', $this->hasher->hash('old password!'), tenantId: 't-1'))->withFailedLogin(1, 900));
        self::assertTrue($this->account('ada@example.test')->isLocked());

        $set = $this->runCommand(new UserPasswordCommand(), ['--email' => 'ada@example.test', '--password' => 'new password!']);

        $user = $this->account('ada@example.test');
        self::assertSame(Command::SUCCESS, $set->getStatusCode(), $set->getDisplay());
        self::assertTrue($user->verifyPassword('new password!'));
        self::assertFalse($user->verifyPassword('old password!'));
        self::assertFalse($user->isLocked(), 'the description promises the lockout goes too');
        self::assertSame(0, $user->getFailedAttempts());
        self::assertFalse($user->isPasswordIssuedByOperator(), 'not demanded unless asked for');
    }

    #[Test]
    public function user_password_must_change_makes_the_owner_replace_it(): void
    {
        $this->users->save(new PlatformUser('u-1', 'ada@example.test', $this->hasher->hash('old password!'), tenantId: 't-1'));

        $set = $this->runCommand(new UserPasswordCommand(), ['--email' => 'ada@example.test', '--password' => 'issued password', '--must-change' => true]);

        self::assertTrue($this->account('ada@example.test')->isPasswordIssuedByOperator());
        self::assertStringContainsString('must replace it', $set->getDisplay());
    }

    #[Test]
    public function an_address_on_two_tenants_needs_the_tenant_named(): void
    {
        $this->users->save(new PlatformUser('u-1', 'ada@example.test', $this->hasher->hash('first password'), tenantId: 't-1'));
        $this->users->save(new PlatformUser('u-2', 'ada@example.test', $this->hasher->hash('second password'), tenantId: 't-2'));

        $ambiguous = $this->runCommand(new UserPasswordCommand(), ['--email' => 'ada@example.test', '--password' => 'new password!']);
        $named = $this->runCommand(new UserPasswordCommand(), ['--email' => 'ada@example.test', '--tenant' => 't-2', '--password' => 'new password!']);

        self::assertSame(Command::FAILURE, $ambiguous->getStatusCode());
        self::assertStringContainsString('Pass --tenant', $ambiguous->getDisplay());
        self::assertSame(Command::SUCCESS, $named->getStatusCode());
        self::assertTrue($this->account('ada@example.test', 't-1')->verifyPassword('first password'), 'the other site is untouched');
        self::assertTrue($this->account('ada@example.test', 't-2')->verifyPassword('new password!'));
    }

    #[Test]
    public function user_disable_revokes_access_and_enable_restores_it(): void
    {
        $this->users->save(new PlatformUser('u-1', 'ada@example.test', $this->hasher->hash('correct horse'), tenantId: 't-1'));

        $off = $this->runCommand(new UserDisableCommand(), ['--email' => 'ada@example.test']);
        $disabled = $this->account('ada@example.test');
        $listed = $this->runCommand(new UserListCommand(), []);
        $listedAll = $this->runCommand(new UserListCommand(), ['--all' => true]);
        $on = $this->runCommand(new UserDisableCommand(), ['--email' => 'ada@example.test', '--enable' => true]);

        self::assertSame(Command::SUCCESS, $off->getStatusCode());
        self::assertSame(UserStatus::Disabled, $disabled->getStatus());
        self::assertFalse($disabled->canAuthenticate(), 'a disabled account cannot sign in');
        self::assertStringContainsString('No users.', $listed->getDisplay(), 'disabled accounts are hidden by default');
        self::assertStringContainsString('disabled', $listedAll->getDisplay());
        self::assertSame(Command::SUCCESS, $on->getStatusCode());
        self::assertTrue($this->account('ada@example.test')->canAuthenticate());
    }

    #[Test]
    public function user_list_shows_each_account_with_its_tenant_role_and_password_state(): void
    {
        $this->users->save(new PlatformUser('u-1', 'ada@example.test', $this->hasher->hash('correct horse'), tenantId: 't-1', role: UserRole::Editor));
        $this->users->save((new PlatformUser('u-2', 'root@example.test', $this->hasher->hash('correct horse'), role: UserRole::Owner))->withPasswordIssuedByOperator($this->hasher->hash('issued password')));

        $all = $this->runCommand(new UserListCommand(), [])->getDisplay();
        $one = $this->runCommand(new UserListCommand(), ['--tenant' => 't-1'])->getDisplay();

        self::assertMatchesRegularExpression('/ada@example\.test\s*\|\s*t-1\s*\|\s*editor\s*\|\s*active\s*\|\s*theirs\s*\|\s*never/', $all);
        self::assertMatchesRegularExpression('/root@example\.test\s*\|\s*—\s*\|\s*owner\s*\|\s*active\s*\|\s*must change/', $all);
        self::assertStringContainsString('ada@example.test', $one);
        self::assertStringNotContainsString('root@example.test', $one);
    }

    private function account(string $email, ?string $tenantId = null): PlatformUser
    {
        $user = $this->users->findByEmail($email, $tenantId);
        self::assertNotNull($user, "no single account for {$email}" . ($tenantId !== null ? " on {$tenantId}" : ''));

        return $user;
    }

    /** @param array<string, mixed> $options */
    private function runCommand(Command $command, array $options): CommandTester
    {
        foreach (['users' => $this->users, 'hasher' => $this->hasher] as $property => $value) {
            if (property_exists($command, $property)) {
                (new \ReflectionProperty($command, $property))->setValue($command, $value);
            }
        }
        // The question helper user:add and user:password prompt with lives on the application.
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->execute($options, ['interactive' => false]);

        return $tester;
    }
}

/** The repository's contract, in memory: a null tenant matches only when one account does. */
final class CommandUsers implements PlatformUserRepositoryInterface
{
    /** @var array<string, PlatformUser> */
    private array $rows = [];

    public function findById(string $id): ?PlatformUser
    {
        return $this->rows[$id] ?? null;
    }

    public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser
    {
        $email = strtolower(trim($email));
        $matches = array_values(array_filter(
            $this->rows,
            static fn (PlatformUser $u): bool => $u->getEmail() === $email && ($tenantId === null || $u->getTenantId() === $tenantId),
        ));

        return count($matches) === 1 ? $matches[0] : null;
    }

    public function findAll(?string $tenantId = null, bool $includeDisabled = false): array
    {
        return array_values(array_filter(
            $this->rows,
            static fn (PlatformUser $u): bool => ($tenantId === null || $u->getTenantId() === $tenantId) && ($includeDisabled || $u->getStatus() === UserStatus::Active),
        ));
    }

    public function save(PlatformUser $user): void
    {
        $this->rows[$user->getId()] = $user;
    }

    public function update(PlatformUser $user): void
    {
        if (!isset($this->rows[$user->getId()])) {
            throw new \LogicException('update of an account that was never saved');
        }
        $this->rows[$user->getId()] = $user;
    }

    public function delete(string $id): void
    {
        unset($this->rows[$id]);
    }
}
