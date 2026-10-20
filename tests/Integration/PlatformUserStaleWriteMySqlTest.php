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
}
