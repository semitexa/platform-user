<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Orm\Exception\StaleAggregateException;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * A sign-in reads the account, spends a whole password hash, then writes its
 * bookkeeping back as the full row. A password change that landed in between
 * was overwritten: the old hash and the old credential version came back, and
 * the sessions the change had ended came back with them. row_version (#[Version])
 * now refuses the older write. On the orm:sync'ed platform_user table, under a
 * tenant of its own; skipped without MySQL.
 */
final class PlatformUserStaleWriteMySqlTest extends TestCase
{
    private OrmManager $orm;
    private PlatformUserRepository $users;
    private string $tenant;

    protected function setUp(): void
    {
        try {
            $this->orm = new OrmManager();
            $why = $this->orm->getDriver() !== 'mysql' ? 'not MySQL' : null;
            $this->orm->getAdapter()->query('SELECT row_version, credential_version FROM platform_user LIMIT 0');
        } catch (\Throwable $e) {
            $why = 'MySQL or the synced platform_user table unavailable: ' . $e->getMessage();
        }
        if ($why !== null) {
            if (isset($this->orm)) {
                $this->orm->shutdown();
            }
            self::markTestSkipped($why);
        }

        $this->users = new PlatformUserRepository();
        (new \ReflectionProperty($this->users, 'orm'))->setValue($this->users, $this->orm);
        $this->tenant = 'test-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (isset($this->users)) {
            $this->orm->getAdapter()->execute('DELETE FROM platform_user WHERE tenant_id = :t', ['t' => $this->tenant]);
            $this->orm->shutdown();
        }
    }

    #[Test]
    public function a_sign_in_that_read_before_a_password_change_cannot_write_after_it(): void
    {
        $this->users->save(new PlatformUser(
            id: '',
            email: 'stale@example.test',
            passwordHash: password_hash('the-old-password', PASSWORD_BCRYPT, ['cost' => 4]),
            tenantId: $this->tenant,
        ));
        $signIn = $this->users->findByEmail('stale@example.test', $this->tenant);
        $change = $this->users->findByEmail('stale@example.test', $this->tenant);
        self::assertNotNull($signIn);
        self::assertNotNull($change);

        $this->users->update($change->withPasswordHash(password_hash('the-new-password', PASSWORD_BCRYPT, ['cost' => 4])));

        try {
            $this->users->update($signIn->withFailedLogin(5, 900));
            self::fail('the write from the older read went through');
        } catch (StaleAggregateException) {
        }

        $now = $this->users->findByEmail('stale@example.test', $this->tenant);
        self::assertTrue($now?->verifyPassword('the-new-password'));
        self::assertSame(1, $now?->getCredentialVersion());
    }

    /** From the row as read, fifty guesses at once were all checked, and the account never locked. */
    #[Test]
    public function failures_counted_from_stale_reads_still_lock_the_account(): void
    {
        $this->users->save(new PlatformUser(
            id: '',
            email: 'burst@example.test',
            passwordHash: password_hash('the-password', PASSWORD_BCRYPT, ['cost' => 4]),
            tenantId: $this->tenant,
        ));
        $stale = $this->users->findByEmail('burst@example.test', $this->tenant);
        self::assertNotNull($stale);

        // Taken before each check: of seven at once, five are checked.
        $taken = array_map(fn (): bool => $this->users->reserveLoginAttempt($stale, 5), range(1, 7));
        self::assertSame([true, true, true, true, true, false, false], $taken);

        $now = $this->users->lockIfExhausted($stale, 5, 900);
        self::assertSame(5, $now?->getFailedAttempts());
        self::assertTrue($now->isLocked());
    }

    /** One miss after a lockout ran out locked again for the full window: one request per window kept anybody out. */
    #[Test]
    public function a_lockout_that_ran_out_starts_the_count_again(): void
    {
        $this->users->save(new PlatformUser(
            id: '',
            email: 'expired@example.test',
            passwordHash: password_hash('the-password', PASSWORD_BCRYPT, ['cost' => 4]),
            tenantId: $this->tenant,
        ));
        $user = $this->users->findByEmail('expired@example.test', $this->tenant);
        self::assertNotNull($user);
        $this->orm->getAdapter()->execute(
            "UPDATE platform_user SET failed_attempts = 5, locked_until = '2001-01-01 00:00:00' WHERE tenant_id = :t",
            ['t' => $this->tenant],
        );

        self::assertTrue($this->users->reserveLoginAttempt($user, 5));
        $after = $this->users->lockIfExhausted($user, 5, 900);

        self::assertSame(1, $after?->getFailedAttempts());
        self::assertFalse($after->isLocked());
    }

    /** Attempts used up with no lock written (its write failed): every later attempt was refused, for good. */
    #[Test]
    public function used_up_attempts_with_no_lock_become_a_lockout_that_ends(): void
    {
        $this->users->save(new PlatformUser(
            id: '',
            email: 'stuck@example.test',
            passwordHash: password_hash('the-password', PASSWORD_BCRYPT, ['cost' => 4]),
            tenantId: $this->tenant,
        ));
        $this->orm->getAdapter()->execute('UPDATE platform_user SET failed_attempts = 5, locked_until = NULL WHERE tenant_id = :t', ['t' => $this->tenant]);
        $auth = new \Semitexa\Platform\User\Application\Service\UserAuthenticator();
        foreach (['users' => $this->users, 'hasher' => new \Semitexa\Platform\User\Application\Service\PasswordHasher(), 'maxAttempts' => 5, 'lockSeconds' => 900] as $property => $value) {
            (new \ReflectionProperty($auth, $property))->setValue($auth, $value);
        }

        $auth->attempt('stuck@example.test', 'the-password', $this->tenant);

        self::assertNotNull($this->users->findByEmail('stuck@example.test', $this->tenant)?->getLockedUntil(), 'a lockout with an end');
    }
}
