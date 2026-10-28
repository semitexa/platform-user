<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Event\EventDispatcherInterface;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * A UserDeleted listener removes what it keeps for the user. One that threw
 * after the row was gone left the settings behind and the caller with an
 * exception for a delete that had happened. On the orm:sync'ed platform_user
 * table, under a tenant of its own.
 */
final class UserDeleteIsAtomicMySqlTest extends TestCase
{
    private OrmManager $orm;
    private PlatformUserRepository $users;
    private string $tenant;

    protected function setUp(): void
    {
        $this->orm = new OrmManager();
        self::assertSame('mysql', $this->orm->getDriver(), 'bin/semitexa test:run runs on MySQL');
        $this->users = new PlatformUserRepository();
        (new \ReflectionProperty($this->users, 'orm'))->setValue($this->users, $this->orm);
        $this->tenant = 'test-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        $this->orm->getAdapter()->execute('DELETE FROM platform_user WHERE tenant_id = :t', ['t' => $this->tenant]);
        $this->orm->shutdown();
    }

    #[Test]
    public function a_listener_that_fails_keeps_the_user(): void
    {
        $this->users->save(new PlatformUser(id: '', email: 'gone@example.test', passwordHash: 'x', tenantId: $this->tenant));
        $user = $this->users->findByEmail('gone@example.test', $this->tenant);
        self::assertNotNull($user);

        (new \ReflectionProperty($this->users, 'events'))->setValue($this->users, new class implements EventDispatcherInterface {
            public function create(string $eventClass, array $payload): object { return new \stdClass(); }
            public function dispatch(object $event): void { throw new \RuntimeException('settings store down'); }
            public function addPostDispatchHook(callable $hook): void {}
        });

        try {
            $this->users->delete($user->getId());
            self::fail('the listener failure must reach the caller');
        } catch (\RuntimeException $e) {
            self::assertSame('settings store down', $e->getMessage());
        }

        self::assertNotNull($this->users->findById($user->getId()), 'the row stays with what the listener could not remove');
    }
}
