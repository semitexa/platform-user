<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Orm\Trait\Seedable;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Domain\Model\RolePermission;

#[FromTable(name: 'platform_role_permissions', mapTo: RolePermission::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'role_id', 'permission_id'], unique: true, name: 'uniq_platform_role_permissions')]
class RolePermissionResource implements DomainMappable
{
    use HasUuidV7;
    use Seedable;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $role_id = '';

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $permission_id = '';

    public static function defaults(): array
    {
        $adminRoleId = self::normalizeUuid('01900000-0000-7000-8000-000000000010');
        return [
            self::create(id: self::normalizeUuid('01900000-0000-7000-8000-000000000020'), role_id: $adminRoleId, permission_id: self::normalizeUuid('01900000-0000-7000-8000-000000000001')),
            self::create(id: self::normalizeUuid('01900000-0000-7000-8000-000000000021'), role_id: $adminRoleId, permission_id: self::normalizeUuid('01900000-0000-7000-8000-000000000002')),
            self::create(id: self::normalizeUuid('01900000-0000-7000-8000-000000000022'), role_id: $adminRoleId, permission_id: self::normalizeUuid('01900000-0000-7000-8000-000000000003')),
            self::create(id: self::normalizeUuid('01900000-0000-7000-8000-000000000023'), role_id: $adminRoleId, permission_id: self::normalizeUuid('01900000-0000-7000-8000-000000000004')),
        ];
    }

    public function toDomain(): RolePermission
    {
        return new RolePermission(
            id: $this->id,
            roleId: $this->role_id,
            permissionId: $this->permission_id,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof RolePermission);
        $r = new static();
        $r->id = self::normalizeUuid($entity->id);
        $r->role_id = self::normalizeUuid($entity->roleId);
        $r->permission_id = self::normalizeUuid($entity->permissionId);
        return $r;
    }

    private static function normalizeUuid(string $value): string
    {
        if (strlen($value) === 36 && str_contains($value, '-')) {
            return Uuid7::toBytes($value);
        }

        return $value;
    }
}
