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
use Semitexa\Platform\User\Domain\Model\ProfileValue;

#[FromTable(name: 'platform_user_profile_values', mapTo: ProfileValue::class)]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'user_id', 'field_id'], unique: true, name: 'uniq_platform_profile_values_user_field')]
class ProfileValueResource implements DomainMappable
{
    use HasUuidV7;
    use HasTimestamps;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $user_id = '';

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $field_id = '';

    #[Column(type: MySqlType::Text, nullable: true)]
    public ?string $value = null;

    #[Column(type: MySqlType::Binary, length: 16, nullable: true)]
    public ?string $file_id = null;

    public function toDomain(): ProfileValue
    {
        return new ProfileValue(
            id: $this->id,
            userId: $this->user_id,
            fieldId: $this->field_id,
            value: $this->value,
            fileId: $this->file_id,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof ProfileValue);
        $r = new static();
        $r->id = $entity->id;
        $r->user_id = $entity->userId;
        $r->field_id = $entity->fieldId;
        $r->value = $entity->value;
        $r->file_id = $entity->fileId;
        return $r;
    }
}
