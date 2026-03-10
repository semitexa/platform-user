<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Trait\HasTimestamps;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Domain\Model\UserRole;

#[FromTable(name: 'platform_user_roles', mapTo: UserRole::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'user_id', 'role_id'], unique: true, name: 'uniq_platform_user_roles')]
class UserRoleResource implements DomainMappable
{
    use HasUuidV7;
    use HasTimestamps;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $user_id = '';

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $role_id = '';

    public function toDomain(): UserRole
    {
        return new UserRole(
            id: $this->id,
            userId: $this->user_id,
            roleId: $this->role_id,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof UserRole);
        $r = new static();
        $r->id = self::normalizeUuid($entity->id);
        $r->user_id = self::normalizeUuid($entity->userId);
        $r->role_id = self::normalizeUuid($entity->roleId);
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
