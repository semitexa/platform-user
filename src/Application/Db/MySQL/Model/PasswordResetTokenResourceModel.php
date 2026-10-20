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
 * One password-reset link: the SHA-256 of its token, never the token itself,
 * so a read of this table cannot reset anyone's password.
 *
 * `used_at` is what makes a link single-use: the claim is one UPDATE that sets
 * it only where it is still NULL and the link has not expired, so two requests
 * racing on the same link cannot both win (PasswordResetTokenStore::claim()).
 * `user_id` is the account's id as the domain holds it (a UUID string).
 */
#[FromTable(name: 'platform_user_password_reset')]
#[Index(columns: ['user_id', 'created_at'], name: 'idx_platform_user_password_reset_user')]
#[Index('expires_at')]
final readonly class PasswordResetTokenResourceModel
{
    use HasColumnReferences;

    public function __construct(
        #[PrimaryKey(strategy: 'manual')]
        #[Column(name: 'token_hash', type: MySqlType::Char, length: 64)]
        public string $tokenHash,

        #[Column(name: 'user_id', type: MySqlType::Varchar, length: 36)]
        public string $userId,

        #[Column(name: 'tenant_id', type: MySqlType::Varchar, length: 64)]
        public string $tenantId,

        #[Column(name: 'created_at', type: MySqlType::Datetime)]
        public \DateTimeImmutable $createdAt,

        #[Column(name: 'expires_at', type: MySqlType::Datetime)]
        public \DateTimeImmutable $expiresAt,

        #[Column(name: 'used_at', type: MySqlType::Datetime, nullable: true)]
        public ?\DateTimeImmutable $usedAt = null,
    ) {}
}
