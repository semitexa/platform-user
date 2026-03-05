<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Platform\User\Domain\Model\Permission;

#[FromTable(name: 'platform_permissions', mapTo: Permission::class)]
class PermissionResource implements DomainMappable
{
    use HasUuidV7;

    #[Column(type: MySqlType::Varchar, length: 128)]
    public string $slug = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $name = '';

    #[Column(type: MySqlType::Varchar, length: 64)]
    public string $group_key = '';

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
