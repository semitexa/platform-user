<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Core\Tenant\DefaultTenantContext;
use Semitexa\Core\Tenant\TenantContextInterface;
use Semitexa\Core\Tenant\TenantContextStoreInterface;
use Semitexa\Core\Tenant\Layer\TenantLayerInterface;
use Semitexa\Core\Tenant\Layer\TenantLayerValueInterface;
use Semitexa\Orm\Domain\Model\ConnectionConfig;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Console\Command\RoleAssignCommand;
use Semitexa\Platform\User\Application\Console\Command\RoleCreateCommand;
use Semitexa\Platform\User\Application\Console\Command\RoleDeleteCommand;
use Semitexa\Platform\User\Application\Console\Command\RoleGrantCommand;
use Semitexa\Platform\User\Application\Service\RoleStore;
use Semitexa\Platform\User\Application\Service\StoredRolePermissionProvider;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Model\PlatformUser;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Stored roles: rbac had only a demo provider, so a project could not keep a
 * role or assign one without writing the storage itself.
 */
final class StoredRolesTest extends TestCase
{
    private const ADA = '0192f6a0-0000-7000-8000-00000000000a';
    private const BOB = '0192f6a0-0000-7000-8000-00000000000c';

    private OrmManager $orm;
    private RoleStore $roles;
    private RoleUsers $users;

    protected function setUp(): void
    {
        $this->orm = new OrmManager(config: new ConnectionConfig(driver: 'sqlite', sqliteMemory: true));
        $this->orm->getAdapter()->execute('CREATE TABLE platform_role (id VARCHAR(36) PRIMARY KEY, tenant_id VARCHAR(64) NOT NULL, name VARCHAR(64) NOT NULL, permissions TEXT NOT NULL, created_at DATETIME NOT NULL, UNIQUE (tenant_id, name))');
        $this->orm->getAdapter()->execute('CREATE TABLE platform_user_role (id VARCHAR(36) PRIMARY KEY, user_id VARCHAR(36) NOT NULL, role_id VARCHAR(36) NOT NULL, created_at DATETIME NOT NULL, UNIQUE (user_id, role_id))');
        $this->roles = new RoleStore();
        (new \ReflectionProperty(RoleStore::class, 'orm'))->setValue($this->roles, $this->orm);
        $this->users = new RoleUsers();
        $this->users->save(new PlatformUser(id: self::ADA, email: 'ada@museum.test', passwordHash: 'x', tenantId: 'museum', role: UserRole::Admin));
        $this->users->save(new PlatformUser(id: self::BOB, email: 'bob@clinic.test', passwordHash: 'x', tenantId: 'clinic', role: UserRole::Admin));
    }

    protected function tearDown(): void
    {
        $this->orm->shutdown();
    }

    #[Test]
    public function a_role_assigned_from_the_console_grants_its_permissions_through_rbac(): void
    {
        self::assertSame(Command::SUCCESS, $this->console(new RoleCreateCommand(), ['name' => 'curator', '--permission' => ['exhibits.write', 'Exhibits.Read'], '--tenant' => 'museum'])->getStatusCode());
        self::assertSame(Command::SUCCESS, $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'curator'])->getStatusCode());

        $provider = $this->provider();
        self::assertSame(['exhibits.read', 'exhibits.write'], $provider->getPermissionsForUser(self::ADA));

        self::assertSame(Command::SUCCESS, $this->console(new RoleGrantCommand(), ['role' => 'curator', '--tenant' => 'museum', '--permission' => ['loans.manage'], '--revoke' => ['Exhibits.Write']])->getStatusCode(), 'a revoke is lower-cased like a grant');
        self::assertSame(['exhibits.read', 'loans.manage'], $provider->getPermissionsForUser(self::ADA), 'seen on the next read: nothing cached here');

        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'curator', '--remove' => true]);
        self::assertSame([], $provider->getPermissionsForUser(self::ADA));
    }

    #[Test]
    public function one_site_s_role_cannot_be_given_to_another_site_s_user(): void
    {
        $this->console(new RoleCreateCommand(), ['name' => 'curator', '--permission' => ['exhibits.write'], '--tenant' => 'museum']);

        $assign = $this->console(new RoleAssignCommand(), ['--email' => 'bob@clinic.test', 'role' => 'curator']);

        self::assertSame(Command::FAILURE, $assign->getStatusCode());
        self::assertSame([], $this->roles->permissionsOf(self::BOB, 'clinic'));
    }

    #[Test]
    public function a_shared_role_goes_to_any_site_s_user_and_its_deletion_takes_it_back(): void
    {
        $id = $this->roles->create('', 'auditor', ['reports.read']);
        $this->console(new RoleAssignCommand(), ['--email' => 'bob@clinic.test', 'role' => 'auditor']);
        self::assertSame(['reports.read'], $this->roles->permissionsOf(self::BOB, 'clinic'));

        $this->roles->delete($id);

        self::assertSame([], $this->roles->permissionsOf(self::BOB, 'clinic'));
        self::assertSame([], $this->orm->getAdapter()->execute('SELECT * FROM platform_user_role')->fetchAll(), 'its assignments go with it');
    }

    #[Test]
    public function a_bad_name_or_slug_and_a_duplicate_are_refused(): void
    {
        $this->roles->create('', 'auditor', []);

        foreach ([['', 'Auditor Team', []], ['', 'ok', ['drop table']], ['', 'auditor', []]] as [$tenant, $name, $perms]) {
            try {
                $this->roles->create($tenant, $name, $perms);
                self::fail("{$name} should be refused");
            } catch (\InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
        self::assertCount(1, $this->roles->all());
    }

    /** One session can reach several sites: a site's role is that site's only. */
    #[Test]
    public function a_site_s_role_grants_nothing_on_another_site(): void
    {
        $this->roles->create('museum', 'curator', ['exhibits.write']);
        $this->roles->create('', 'auditor', ['reports.read']);
        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'curator']);
        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'auditor']);

        self::assertSame(['exhibits.write', 'reports.read'], $this->roles->permissionsOf(self::ADA, 'museum'));
        self::assertSame(['reports.read'], $this->roles->permissionsOf(self::ADA, 'clinic'), 'only the shared role on the clinic\'s site');
        self::assertSame([], $this->provider()->getPermissionsForUser('google:ada@museum.test'), 'another sign-in method\'s id has no stored roles');
    }

    /** With a site's role and a shared one of the same name, --shared names the shared one. */
    #[Test]
    public function the_shared_role_of_a_name_a_site_also_uses_can_be_taken_back(): void
    {
        $this->roles->create('', 'curator', ['shared.read']);
        $this->roles->create('museum', 'curator', ['museum.write']);
        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'curator', '--shared' => true]);
        self::assertSame(['shared.read'], $this->roles->permissionsOf(self::ADA, 'museum'));

        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'curator', '--shared' => true, '--remove' => true]);

        self::assertSame([], $this->roles->permissionsOf(self::ADA, 'museum'));
    }

    /** A forgotten --tenant changed every site's shared role. */
    #[Test]
    public function changing_or_deleting_a_role_says_which_one(): void
    {
        $this->roles->create('', 'curator', ['shared.read']);

        self::assertSame(Command::FAILURE, $this->console(new RoleDeleteCommand(), ['role' => 'curator'])->getStatusCode());
        self::assertSame(Command::FAILURE, $this->console(new RoleGrantCommand(), ['role' => 'curator', '--permission' => ['x.y']])->getStatusCode());
        self::assertCount(1, $this->roles->all());
        self::assertSame(Command::SUCCESS, $this->console(new RoleDeleteCommand(), ['role' => 'curator', '--shared' => true])->getStatusCode());
        self::assertSame([], $this->roles->all());
    }

    /**
     * The provider, by the request's tenant: no tenancy (the user's own site),
     * the root site (shared roles only: data is not filtered there), another
     * site (shared only), the user's site (both).
     */
    #[Test]
    public function the_provider_grants_by_the_site_the_request_is_for(): void
    {
        $this->roles->create('museum', 'curator', ['exhibits.write']);
        $this->roles->create('', 'auditor', ['reports.read']);
        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'curator']);
        $this->console(new RoleAssignCommand(), ['--email' => 'ada@museum.test', 'role' => 'auditor']);

        self::assertSame(['exhibits.write', 'reports.read'], $this->provider(null)->getPermissionsForUser(self::ADA), 'no tenancy: her own site');
        self::assertSame(['reports.read'], $this->provider(new DefaultTenantContext())->getPermissionsForUser(self::ADA), 'the root site: shared only');
        self::assertSame(['reports.read'], $this->provider(self::tenant('clinic'))->getPermissionsForUser(self::ADA));
        self::assertSame(['exhibits.write', 'reports.read'], $this->provider(self::tenant('museum'))->getPermissionsForUser(self::ADA));
    }

    #[Test]
    public function a_role_is_created_for_a_site_or_shared_said_out_loud(): void
    {
        self::assertSame(Command::FAILURE, $this->console(new RoleCreateCommand(), ['name' => 'admin', '--permission' => ['x.y']])->getStatusCode());
        self::assertSame([], $this->roles->all());
        self::assertSame(Command::SUCCESS, $this->console(new RoleCreateCommand(), ['name' => 'admin', '--shared' => true])->getStatusCode());
    }

    private static function tenant(string $id): TenantContextInterface
    {
        return new class ($id) implements TenantContextInterface {
            public function __construct(private string $id) {}
            public function getTenantId(): string { return $this->id; }
            public function isDefault(): bool { return false; }
            public function getLayer(TenantLayerInterface $layer): ?TenantLayerValueInterface { return null; }
            public function hasLayer(TenantLayerInterface $layer): bool { return false; }
        };
    }

    private function provider(?TenantContextInterface $context = null): StoredRolePermissionProvider
    {
        $provider = new StoredRolePermissionProvider();
        (new \ReflectionProperty(StoredRolePermissionProvider::class, 'roles'))->setValue($provider, $this->roles);
        (new \ReflectionProperty(StoredRolePermissionProvider::class, 'users'))->setValue($provider, $this->users);
        if ($context !== null) {
            (new \ReflectionProperty(StoredRolePermissionProvider::class, 'tenants'))->setValue($provider, new class ($context) implements TenantContextStoreInterface {
                public function __construct(private TenantContextInterface $context) {}
                public function get(): TenantContextInterface { return $this->context; }
                public function tryGet(): ?TenantContextInterface { return $this->context; }
                public function set(TenantContextInterface $context): void {}
                public function clear(): void {}
            });
        }

        return $provider;
    }

    /** @param array<string, mixed> $input */
    private function console(Command $command, array $input): CommandTester
    {
        foreach (['roles' => $this->roles, 'users' => $this->users] as $property => $value) {
            if (property_exists($command, $property)) {
                (new \ReflectionProperty($command, $property))->setValue($command, $value);
            }
        }
        $command->setApplication(new Application());
        $tester = new CommandTester($command);
        $tester->execute($input, ['interactive' => false]);

        return $tester;
    }
}

final class RoleUsers implements PlatformUserRepositoryInterface
{
    /** @var array<string, PlatformUser> */
    private array $rows = [];

    public function findById(string $id): ?PlatformUser
    {
        return $this->rows[$id] ?? null;
    }

    public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser
    {
        $found = array_values(array_filter($this->rows, static fn (PlatformUser $u): bool => $u->getEmail() === $email && ($tenantId === null || $u->getTenantId() === $tenantId)));

        return count($found) === 1 ? $found[0] : null;
    }

    public function findAll(?string $tenantId = null, bool $includeDisabled = false): array
    {
        return array_values($this->rows);
    }

    public function save(PlatformUser $user): void
    {
        $this->rows[$user->getId()] = $user;
    }

    public function update(PlatformUser $user): void
    {
        $this->rows[$user->getId()] = $user;
    }

    public function delete(string $id): void
    {
        unset($this->rows[$id]);
    }
}
