<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Service\PasswordResetTokenStore;

/**
 * The single-use claim on MySQL, where an UPDATE's row count is the rows it
 * CHANGED: the claim must read 1 exactly once per link (PasswordResetServiceTest
 * covers the flow on SQLite). Runs on the table orm:sync made from
 * PasswordResetTokenResourceModel, under a user id of its own, and deletes only
 * its own rows. Skipped without MySQL or without the table.
 */
final class PasswordResetTokenStoreMySqlTest extends TestCase
{
    private OrmManager $orm;
    private PasswordResetTokenStore $store;
    private string $userId;

    protected function setUp(): void
    {
        try {
            $this->orm = new OrmManager();
            $why = match (true) {
                $this->orm->getDriver() !== 'mysql' => 'not MySQL',
                $this->orm->getAdapter()->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'platform_user_password_reset'")->fetchAll() === []
                    => 'platform_user_password_reset does not exist: run orm:sync',
                default => null,
            };
        } catch (\Throwable $e) {
            $why = 'MySQL unavailable: ' . $e->getMessage();
        }
        if ($why !== null) {
            if (isset($this->orm)) {
                $this->orm->shutdown();
            }
            self::markTestSkipped($why);
        }

        $this->userId = 'test-' . bin2hex(random_bytes(8));
        $this->store = new PasswordResetTokenStore();
        (new \ReflectionProperty($this->store, 'orm'))->setValue($this->store, $this->orm);
    }

    protected function tearDown(): void
    {
        if (isset($this->store)) {
            $this->orm->getAdapter()->execute('DELETE FROM platform_user_password_reset WHERE user_id = :u', ['u' => $this->userId]);
            $this->orm->shutdown();
        }
    }

    #[Test]
    public function a_link_is_claimed_once_while_live_and_never_after(): void
    {
        // Far in the past: purgeExpiredBefore() below must find nothing of anyone else's.
        $now = new \DateTimeImmutable('2001-01-01 12:00:00');
        $u = $this->userId;
        $live = $this->store->issue($u, 'regmus', $now, $now->modify('+1 hour'));
        $expired = $this->store->issue($u, 'regmus', $now, $now->modify('+1 minute'));
        $revoked = $this->store->issue($u, 'regmus', $now, $now->modify('+1 hour'));

        self::assertSame(['user_id' => $u, 'tenant_id' => 'regmus'], $this->store->claim($live, $now->modify('+5 minutes')));
        self::assertNull($this->store->claim($live, $now->modify('+5 minutes')), 'the same second, the second claim');
        self::assertNull($this->store->claim($expired, $now->modify('+2 minutes')));
        self::assertSame(3, $this->store->countIssuedSince($u, $now));

        self::assertSame(2, $this->store->revokeAll($u, $now->modify('+6 minutes')), 'the unused expired link and the live one');
        self::assertNull($this->store->claim($revoked, $now->modify('+7 minutes')));
        self::assertSame(1, $this->store->purgeExpiredBefore($now->modify('+30 minutes')));
    }
}
