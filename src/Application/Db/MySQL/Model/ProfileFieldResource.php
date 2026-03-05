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
use Semitexa\Platform\User\Domain\Model\ProfileField;

#[FromTable(name: 'platform_user_profile_fields', mapTo: ProfileField::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'slug'], unique: true, name: 'uniq_platform_profile_fields_tenant_slug')]
class ProfileFieldResource implements DomainMappable
{
    use HasUuidV7;
    use HasTimestamps;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Varchar, length: 128)]
    public string $slug = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $label = '';

    #[Column(type: MySqlType::Varchar, length: 32)]
    public string $type = 'text';

    #[Column(type: MySqlType::Boolean)]
    public bool $is_required = false;

    #[Column(type: MySqlType::Int)]
    public int $sort_order = 0;

    #[Column(type: MySqlType::Json, nullable: true)]
    public ?string $options = null;

    #[Column(type: MySqlType::Boolean)]
    public bool $is_visible = true;

    #[Column(type: MySqlType::Varchar, length: 16, nullable: true)]
    public ?string $icon = null;

    public function toDomain(): ProfileField
    {
        return new ProfileField(
            id: $this->id,
            slug: $this->slug,
            label: $this->label,
            type: $this->type,
            isRequired: $this->is_required,
            sortOrder: $this->sort_order,
            options: $this->options !== null ? json_decode($this->options, true) ?? [] : [],
            isVisible: $this->is_visible,
            icon: $this->icon,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof ProfileField);
        $r = new static();
        $r->id = $entity->id;
        $r->slug = $entity->slug;
        $r->label = $entity->label;
        $r->type = $entity->type;
        $r->is_required = $entity->isRequired;
        $r->sort_order = $entity->sortOrder;
        $r->options = $entity->options !== [] ? json_encode($entity->options) : null;
        $r->is_visible = $entity->isVisible;
        $r->icon = $entity->icon;
        return $r;
    }
}
