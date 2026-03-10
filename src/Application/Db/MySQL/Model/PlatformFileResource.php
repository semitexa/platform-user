<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Contract\DomainMappable;
use Semitexa\Orm\Trait\HasTimestamps;
use Semitexa\Orm\Trait\HasUuidV7;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Orm\Trait\SoftDeletes;
use Semitexa\Platform\User\Domain\Model\PlatformFile;

#[FromTable(name: 'platform_files', mapTo: PlatformFile::class)]
#[TenantScoped(strategy: 'same_storage')]
class PlatformFileResource implements DomainMappable
{
    use HasUuidV7;
    use HasTimestamps;
    use SoftDeletes;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $original_name = '';

    #[Column(type: MySqlType::Varchar, length: 128)]
    public string $mime_type = '';

    #[Column(type: MySqlType::Int)]
    public int $size = 0;

    #[Column(type: MySqlType::Varchar, length: 512)]
    public string $storage_path = '';

    #[Column(type: MySqlType::Varchar, length: 64)]
    public string $hash = '';

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $uploaded_by = '';

    public function toDomain(): PlatformFile
    {
        return new PlatformFile(
            id: $this->id,
            originalName: $this->original_name,
            mimeType: $this->mime_type,
            size: $this->size,
            storagePath: $this->storage_path,
            hash: $this->hash,
            uploadedBy: $this->uploaded_by,
        );
    }

    public static function fromDomain(object $entity): static
    {
        assert($entity instanceof PlatformFile);
        $r = new static();
        $r->id = self::normalizeUuid($entity->id);
        $r->original_name = $entity->originalName;
        $r->mime_type = $entity->mimeType;
        $r->size = $entity->size;
        $r->storage_path = $entity->storagePath;
        $r->hash = $entity->hash;
        $r->uploaded_by = self::normalizeUuid($entity->uploadedBy);
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
