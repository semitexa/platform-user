<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Model;

use Semitexa\Orm\Adapter\MySqlType;
use Semitexa\Orm\Attribute\Column;
use Semitexa\Orm\Attribute\FromTable;
use Semitexa\Orm\Attribute\Index;
use Semitexa\Orm\Trait\HasTimestamps;
use Semitexa\Orm\Trait\HasUuidV7;

/**
 * Schema owner for the identity table.
 *
 * The unique index is on (tenant_id, email), not on email alone: the same person
 * administering two sites gets one row per site, each with its own role, and the
 * sign-in that follows is unambiguous about which site it grants.
 */
#[FromTable(name: 'platform_user')]
#[Index(columns: ['tenant_id', 'email'], unique: true, name: 'uniq_platform_user_tenant_email')]
#[Index(columns: ['email'], name: 'idx_platform_user_email')]
class PlatformUserResource
{
    use HasUuidV7;
    use HasTimestamps;

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $email = '';

    #[Column(type: MySqlType::LongText)]
    public string $password_hash = '';

    #[Column(type: MySqlType::Varchar, length: 255)]
    public string $display_name = '';

    /** Empty string = bound to no single tenant; only meaningful for an owner. */
    #[Column(type: MySqlType::Varchar, length: 64)]
    public string $tenant_id = '';

    #[Column(type: MySqlType::Varchar, length: 32)]
    public string $role = 'editor';

    #[Column(type: MySqlType::Varchar, length: 16)]
    public string $status = 'active';

    #[Column(type: MySqlType::Datetime, nullable: true)]
    public ?\DateTimeImmutable $last_login_at = null;

    #[Column(type: MySqlType::Int)]
    public int $failed_attempts = 0;

    #[Column(type: MySqlType::Datetime, nullable: true)]
    public ?\DateTimeImmutable $locked_until = null;
}
