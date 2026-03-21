<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\Filterable;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Contract\FilterableResourceInterface;
use Semitexa\Orm\Trait\FilterableTrait;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Orm\Trait\Seedable;
use Semitexa\Platform\User\Domain\Model\Permission;

#[FromTable(name: 'platform_permissions', mapTo: Permission::class)]
class PermissionResource implements DomainMappable, FilterableResourceInterface
{
    use HasUuidV7;
    use FilterableTrait;
    use Seedable;

    #[Filterable]
    #[Column(type: MySqlType::Varchar, length: 128)]
    public string $slug = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $name = '';

    #[Filterable]
    #[Column(type: MySqlType::Varchar, length: 64)]
    public string $group_key = '';

    public static function defaults(): array
    {
        return [
            self::create(
                id: '01900000-0000-7000-8000-000000000001',
                slug: 'users.list',
                name: 'View Users',
                group_key: 'users'
            ),
            self::create(
                id: '01900000-0000-7000-8000-000000000002',
                slug: 'roles.manage',
                name: 'Manage Roles',
                group_key: 'roles'
            ),
            self::create(
                id: '01900000-0000-7000-8000-000000000003',
                slug: 'profile-fields.manage',
                name: 'Manage Profile Fields',
                group_key: 'profile'
            ),
            self::create(
                id: '01900000-0000-7000-8000-000000000004',
                slug: 'platform.settings.manage_global',
                name: 'Manage Global Settings',
                group_key: 'platform'
            ),
        ];
    }

    public function toDomain(): Permission
    {
        return new Permission(
            id: $this->id,
            slug: $this->slug,
            name: $this->name,
            groupKey: $this->group_key,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof Permission);
        $r = new static();
        $r->id = $entity->id;
        $r->slug = $entity->slug;
        $r->name = $entity->name;
        $r->group_key = $entity->groupKey;
        return $r;
    }
}
