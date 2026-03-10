<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\Filterable;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Contract\FilterableResourceInterface;
use Semitexa\Orm\Trait\FilterableTrait;
use Semitexa\Orm\Trait\HasTimestamps;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Platform\User\Domain\Model\Role;

#[FromTable(name: 'platform_roles', mapTo: Role::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'slug'], unique: true, name: 'uniq_platform_roles_tenant_slug')]
class RoleResource implements DomainMappable, FilterableResourceInterface
{
    use HasUuidV7;
    use HasTimestamps;
    use FilterableTrait;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Filterable]
    #[Column(type: MySqlType::Varchar, length: 64)]
    public string $slug = '';

    #[Filterable]
    #[Column(type: MySqlType::Varchar, length: 128)]
    public string $name = '';

    #[Column(type: MySqlType::Varchar, length: 512, nullable: true)]
    public ?string $description = null;

    #[Column(type: MySqlType::Boolean)]
    public bool $is_system = false;

    public function toDomain(): Role
    {
        return new Role(
            id: $this->id,
            slug: $this->slug,
            name: $this->name,
            description: $this->description,
            isSystem: $this->is_system,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof Role);
        $r = new static();
        $r->id = $entity->id;
        $r->slug = $entity->slug;
        $r->name = $entity->name;
        $r->description = $entity->description;
        $r->is_system = $entity->isSystem;
        return $r;
    }
}
