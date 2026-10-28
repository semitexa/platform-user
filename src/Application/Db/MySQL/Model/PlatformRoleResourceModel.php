<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\PrimaryKey;
use Semitexa\Orm\Metadata\HasColumnReferences;

/**
 * A stored role: a name and the permission slugs it grants, which semitexa/rbac
 * reads through StoredRolePermissionProvider. `tenant_id` '' is a role every
 * site can use; otherwise only that tenant's users can hold it.
 */
#[FromTable(name: 'platform_role')]
#[Index(columns: ['tenant_id', 'name'], unique: true, name: 'uniq_platform_role_tenant_name')]
final readonly class PlatformRoleResourceModel
{
    use HasColumnReferences;

    public function __construct(
        #[PrimaryKey(strategy: 'manual')]
        #[Column(name: 'id', type: MySqlType::Varchar, length: 36)]
        public string $id,

        #[Column(name: 'tenant_id', type: MySqlType::Varchar, length: 64)]
        public string $tenantId,

        #[Column(name: 'name', type: MySqlType::Varchar, length: 64)]
        public string $name,

        /** JSON list of permission slugs. */
        #[Column(name: 'permissions', type: MySqlType::Text)]
        public string $permissions,

        #[Column(name: 'created_at', type: MySqlType::Datetime)]
        public \DateTimeImmutable $createdAt,
    ) {}
}
