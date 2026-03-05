<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Attribute\TenantScoped;
use Semitexa\Orm\Trait\HasTimestamps;
use Semitexa\Orm\Trait\HasUuidV7;

#[FromTable(name: 'platform_user_roles')]
#[TenantScoped(strategy: 'same_storage')]
#[Index(columns: ['tenant_id', 'user_id', 'role_id'], unique: true, name: 'uniq_platform_user_roles')]
class UserRoleResource
{
    use HasUuidV7;
    use HasTimestamps;

    #[Column(type: MySqlType::Varchar, length: 64, nullable: true)]
    public ?string $tenant_id = null;

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $user_id = '';

    #[Column(type: MySqlType::Binary, length: 16)]
    public string $role_id = '';
}
