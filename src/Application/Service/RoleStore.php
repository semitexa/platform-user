<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Orm\Adapter\SqlIdentifier;
use Semitexa\Orm\Application\Service\Uuid7;
use Semitexa\Orm\OrmManager;

/**
 * Stored roles (`platform_role`) and who holds them (`platform_user_role`):
 * what semitexa/rbac grants a signed-in user, through
 * StoredRolePermissionProvider. A role belongs to one tenant, or to every
 * tenant when its tenant id is ''.
 */
#[AsService]
final class RoleStore
{
    public const ROLES = 'platform_role';
    public const ASSIGNMENTS = 'platform_user_role';

    /** A permission slug as rbac checks it: `orders.manage`, `reports.read`. */
    private const SLUG = '/^[a-z0-9][a-z0-9_.:-]{0,127}$/';

    private const NAME = '/^[a-z0-9][a-z0-9_.-]{0,63}$/';

    #[InjectAsReadonly]
    protected OrmManager $orm;

    /**
     * @param list<string> $permissions
     * @return string the role's id
     */
    public function create(string $tenantId, string $name, array $permissions): string
    {
        self::assertName($name);
        if (strlen($tenantId) > 64 || trim($tenantId) !== $tenantId) {
            throw new \InvalidArgumentException("'{$tenantId}' is not a tenant id (at most 64 characters, no surrounding spaces).");
        }
        if ($this->find($tenantId, $name) !== null) {
            throw new \InvalidArgumentException("A role named '{$name}' already exists" . ($tenantId === '' ? '.' : " for tenant '{$tenantId}'."));
        }
        $id = Uuid7::generate();
        $permissions = self::slugs($permissions);
        try {
            $this->orm()->getQueryAdapter()->execute(sprintf(
                'INSERT INTO %s (id, tenant_id, name, permissions, created_at) VALUES (:id, :tenant_id, :name, :permissions, :created_at)',
                SqlIdentifier::quote(self::ROLES),
            ), [
                'id' => $id,
                'tenant_id' => $tenantId,
                'name' => $name,
                'permissions' => self::encode($permissions),
                'created_at' => gmdate('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Another create of the same name between the check and here:
            // the unique index stopped it.
            if ($this->find($tenantId, $name) !== null) {
                throw new \InvalidArgumentException("A role named '{$name}' already exists.", 0, $e);
            }
            throw $e;
        }

        return $id;
    }

    /** @return array{id: string, tenant_id: string, name: string, permissions: list<string>}|null */
    public function find(string $tenantId, string $name): ?array
    {
        $row = $this->orm()->getQueryAdapter()->execute(sprintf(
            'SELECT id, tenant_id, name, permissions FROM %s WHERE tenant_id = :tenant_id AND name = :name',
            SqlIdentifier::quote(self::ROLES),
        ), ['tenant_id' => $tenantId, 'name' => $name])->fetchOne();

        return $row === null ? null : self::role($row);
    }

    /**
     * Every role of $tenantId and every shared one; all roles when null.
     *
     * @return list<array{id: string, tenant_id: string, name: string, permissions: list<string>}>
     */
    public function all(?string $tenantId = null): array
    {
        $sql = sprintf('SELECT id, tenant_id, name, permissions FROM %s', SqlIdentifier::quote(self::ROLES));
        $params = [];
        if ($tenantId !== null) {
            $sql .= " WHERE tenant_id IN ('', :tenant_id)";
            $params['tenant_id'] = $tenantId;
        }

        return array_map(self::role(...), $this->orm()->getQueryAdapter()->execute($sql . ' ORDER BY tenant_id, name', $params)->fetchAll());
    }

    /**
     * Add and remove permission slugs of a role.
     *
     * @param list<string> $add
     * @param list<string> $remove
     * @return list<string> the role's permissions afterwards
     */
    public function changePermissions(string $roleId, array $add, array $remove): array
    {
        $row = $this->orm()->getQueryAdapter()->execute(sprintf(
            'SELECT id, tenant_id, name, permissions FROM %s WHERE id = :id',
            SqlIdentifier::quote(self::ROLES),
        ), ['id' => $roleId])->fetchOne();
        if ($row === null) {
            throw new \InvalidArgumentException("No role with id {$roleId}.");
        }
        $permissions = array_values(array_diff(
            array_values(array_unique([...self::role($row)['permissions'], ...self::slugs($add)])),
            array_map(static fn (string $slug): string => strtolower(trim($slug)), $remove),
        ));
        sort($permissions);
        $this->orm()->getQueryAdapter()->execute(sprintf(
            'UPDATE %s SET permissions = :permissions WHERE id = :id',
            SqlIdentifier::quote(self::ROLES),
        ), ['permissions' => self::encode($permissions), 'id' => $roleId]);

        return $permissions;
    }

    /** Delete a role and every assignment of it: the assignments first, so none is left granting a role that is gone. */
    public function delete(string $roleId): void
    {
        $adapter = $this->orm()->getQueryAdapter();
        $adapter->execute(sprintf('DELETE FROM %s WHERE role_id = :id', SqlIdentifier::quote(self::ASSIGNMENTS)), ['id' => $roleId]);
        $adapter->execute(sprintf('DELETE FROM %s WHERE id = :id', SqlIdentifier::quote(self::ROLES)), ['id' => $roleId]);
    }

    /** @return bool false when the user already held it */
    public function assign(string $userId, string $roleId): bool
    {
        if ($this->holds($userId, $roleId)) {
            return false;
        }
        try {
            $this->orm()->getQueryAdapter()->execute(sprintf(
                'INSERT INTO %s (id, user_id, role_id, created_at) VALUES (:id, :user_id, :role_id, :created_at)',
                SqlIdentifier::quote(self::ASSIGNMENTS),
            ), ['id' => Uuid7::generate(), 'user_id' => $userId, 'role_id' => $roleId, 'created_at' => gmdate('Y-m-d H:i:s')]);
        } catch (\Throwable $e) {
            // The same assignment made between the check and here (unique index).
            if ($this->holds($userId, $roleId)) {
                return false;
            }
            throw $e;
        }

        return true;
    }

    private function holds(string $userId, string $roleId): bool
    {
        return $this->orm()->getQueryAdapter()->execute(sprintf(
            'SELECT 1 AS held FROM %s WHERE user_id = :user_id AND role_id = :role_id',
            SqlIdentifier::quote(self::ASSIGNMENTS),
        ), ['user_id' => $userId, 'role_id' => $roleId])->fetchOne() !== null;
    }

    /** @return bool false when the user did not hold it */
    public function unassign(string $userId, string $roleId): bool
    {
        return $this->orm()->getQueryAdapter()->execute(sprintf(
            'DELETE FROM %s WHERE user_id = :user_id AND role_id = :role_id',
            SqlIdentifier::quote(self::ASSIGNMENTS),
        ), ['user_id' => $userId, 'role_id' => $roleId])->rowCount > 0;
    }

    /**
     * The user's roles; with $tenantId, only the shared ones and that tenant's.
     *
     * @return list<array{id: string, tenant_id: string, name: string, permissions: list<string>}>
     */
    public function rolesOf(string $userId, ?string $tenantId = null): array
    {
        $sql = sprintf(
            'SELECT r.id, r.tenant_id, r.name, r.permissions FROM %s r JOIN %s a ON a.role_id = r.id WHERE a.user_id = :user_id',
            SqlIdentifier::quote(self::ROLES),
            SqlIdentifier::quote(self::ASSIGNMENTS),
        );
        $params = ['user_id' => $userId];
        if ($tenantId !== null) {
            $sql .= " AND r.tenant_id IN ('', :tenant_id)";
            $params['tenant_id'] = $tenantId;
        }

        return array_map(self::role(...), $this->orm()->getQueryAdapter()->execute($sql . ' ORDER BY r.name', $params)->fetchAll());
    }

    /**
     * Every permission slug the user's roles grant on $tenantId's site: its own
     * roles and the shared ones, never another site's.
     *
     * @return list<string>
     */
    public function permissionsOf(string $userId, string $tenantId): array
    {
        $slugs = [];
        foreach ($this->rolesOf($userId, $tenantId) as $role) {
            foreach ($role['permissions'] as $slug) {
                $slugs[$slug] = true;
            }
        }

        $slugs = array_keys($slugs);
        sort($slugs);

        return $slugs;
    }

    private function orm(): OrmManager
    {
        return $this->orm;
    }

    /**
     * @param array<string, mixed> $row
     * @return array{id: string, tenant_id: string, name: string, permissions: list<string>}
     */
    private static function role(array $row): array
    {
        $permissions = json_decode((string) ($row['permissions'] ?? '[]'), true);

        return [
            'id' => (string) $row['id'],
            'tenant_id' => (string) $row['tenant_id'],
            'name' => (string) $row['name'],
            'permissions' => is_array($permissions) ? array_values(array_filter($permissions, 'is_string')) : [],
        ];
    }

    /**
     * @param list<string> $permissions
     * @return list<string>
     */
    private static function slugs(array $permissions): array
    {
        $slugs = [];
        foreach ($permissions as $permission) {
            $permission = strtolower(trim($permission));
            if (preg_match(self::SLUG, $permission) !== 1) {
                throw new \InvalidArgumentException("'{$permission}' is not a permission slug (letters, digits and . _ : -, such as orders.manage).");
            }
            $slugs[$permission] = true;
        }
        $slugs = array_keys($slugs);
        sort($slugs);

        return $slugs;
    }

    private static function assertName(string $name): void
    {
        if (preg_match(self::NAME, $name) !== 1) {
            throw new \InvalidArgumentException("'{$name}' is not a role name (lower-case letters, digits and . _ -).");
        }
    }

    /** @param list<string> $permissions */
    private static function encode(array $permissions): string
    {
        return json_encode(array_values($permissions), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
