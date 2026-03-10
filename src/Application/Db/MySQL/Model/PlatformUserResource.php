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
use Semitexa\Platform\User\Domain\Model\User;

#[FromTable(name: 'platform_users', mapTo: User::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'email'], unique: true, name: 'uniq_platform_users_tenant_email')]
class PlatformUserResource implements DomainMappable, FilterableResourceInterface
{
    use HasUuidV7;
    use HasTimestamps;
    use FilterableTrait;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Filterable]
    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $email = '';

    #[Filterable]
    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $name = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $password_hash = '';

    #[Filterable]
    #[Column(type: MySqlType::Boolean)]
    public bool $is_active = true;

    public function toDomain(): User
    {
        return new User(
            id: $this->id,
            email: $this->email,
            name: $this->name,
            isActive: $this->is_active,
            tenantId: $this->tenant_id,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof User);
        $r = new static();
        $r->id = $entity->id;
        $r->tenant_id = $entity->tenantId;
        $r->email = $entity->email;
        $r->name = $entity->name;
        $r->is_active = $entity->isActive;
        return $r;
    }
}
