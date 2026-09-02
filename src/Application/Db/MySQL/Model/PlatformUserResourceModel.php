<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\PrimaryKey;
use Semitexa\Orm\Metadata\HasColumnReferences;
use Semitexa\Orm\Metadata\HasRelationReferences;

#[FromTable(name: 'platform_user')]
final readonly class PlatformUserResourceModel
{
    use HasColumnReferences;
    use HasRelationReferences;

    public function __construct(
        #[PrimaryKey(strategy: 'uuid')]
        #[Column(type: MySqlType::Binary, length: 16)]
        public string $id,

        #[Column(name: 'email', type: MySqlType::Varchar, length: 255)]
        public string $email,

        #[Column(name: 'password_hash', type: MySqlType::LongText)]
        public string $passwordHash,

        #[Column(name: 'display_name', type: MySqlType::Varchar, length: 255)]
        public string $displayName,

        #[Column(name: 'tenant_id', type: MySqlType::Varchar, length: 64)]
        public string $tenantId,

        #[Column(name: 'role', type: MySqlType::Varchar, length: 32)]
        public string $role,

        #[Column(name: 'status', type: MySqlType::Varchar, length: 16)]
        public string $status,

        #[Column(name: 'created_at', type: MySqlType::Datetime)]
        public \DateTimeImmutable $createdAt,

        #[Column(name: 'updated_at', type: MySqlType::Datetime, nullable: true)]
        public ?\DateTimeImmutable $updatedAt,

        #[Column(name: 'last_login_at', type: MySqlType::Datetime, nullable: true)]
        public ?\DateTimeImmutable $lastLoginAt,

        #[Column(name: 'failed_attempts', type: MySqlType::Int)]
        public int $failedAttempts,

        #[Column(name: 'locked_until', type: MySqlType::Datetime, nullable: true)]
        public ?\DateTimeImmutable $lockedUntil,
    ) {}
}
