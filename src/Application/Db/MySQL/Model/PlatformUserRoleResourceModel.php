<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\PrimaryKey;
use Semitexa\Orm\Metadata\HasColumnReferences;

/** A user holding a stored role (PlatformRoleResourceModel). */
#[FromTable(name: 'platform_user_role')]
#[Index(columns: ['user_id', 'role_id'], unique: true, name: 'uniq_platform_user_role')]
#[Index('role_id')]
final readonly class PlatformUserRoleResourceModel
{
    use HasColumnReferences;

    public function __construct(
        #[PrimaryKey(strategy: 'manual')]
        #[Column(name: 'id', type: MySqlType::Varchar, length: 36)]
        public string $id,

        #[Column(name: 'user_id', type: MySqlType::Varchar, length: 36)]
        public string $userId,

        #[Column(name: 'role_id', type: MySqlType::Varchar, length: 36)]
        public string $roleId,

        #[Column(name: 'created_at', type: MySqlType::Datetime)]
        public \DateTimeImmutable $createdAt,
    ) {}
}
