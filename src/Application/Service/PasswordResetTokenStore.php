<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Orm\Adapter\SqlIdentifier;
use Semitexa\Orm\OrmManager;

/**
 * Password-reset links in `platform_user_password_reset`
 * (PasswordResetTokenResourceModel).
 *
 * The token is 32 random bytes the caller hands out once; the table keeps only
 * its SHA-256. A plain hash is enough here: the token carries 256 bits of
 * entropy, so there is nothing to brute-force that a salt or a slow hash would
 * protect, and an exact hash keeps the lookup an index probe.
 */
#[AsService]
final class PasswordResetTokenStore
{
    public const TABLE = 'platform_user_password_reset';

    private const DATETIME = 'Y-m-d H:i:s';

    #[InjectAsReadonly]
    protected OrmManager $orm;

    /** @return string the token for the link; it exists nowhere else afterwards */
    public function issue(string $userId, string $tenantId, \DateTimeImmutable $now, \DateTimeImmutable $expiresAt, int $credentialVersion = 0): string
    {
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');

        $this->orm()->getQueryAdapter()->execute(sprintf(
            'INSERT INTO %s (token_hash, user_id, tenant_id, created_at, expires_at, used_at, credential_version)'
            . ' VALUES (:token_hash, :user_id, :tenant_id, :created_at, :expires_at, NULL, :credential_version)',
            SqlIdentifier::quote(self::TABLE),
        ), [
            'token_hash' => self::hash($token),
            'user_id'    => $userId,
            'tenant_id'  => $tenantId,
            'created_at' => $now->format(self::DATETIME),
            'expires_at' => $expiresAt->format(self::DATETIME),
            'credential_version' => $credentialVersion,
        ]);

        return $token;
    }

    /** Links issued to this account at or after $since, used or not. */
    public function countIssuedSince(string $userId, \DateTimeImmutable $since): int
    {
        $row = $this->orm()->getQueryAdapter()->execute(sprintf(
            'SELECT COUNT(*) AS n FROM %s WHERE user_id = :user_id AND created_at >= :since',
            SqlIdentifier::quote(self::TABLE),
        ), [
            'user_id' => $userId,
            'since'   => $since->format(self::DATETIME),
        ])->fetchOne();

        return (int) ($row['n'] ?? 0);
    }

    /**
     * Use the link up. One UPDATE sets used_at only where it is still NULL and
     * the link is live, so of two requests racing on one link exactly one gets
     * the row count 1: a read-then-write would let both through.
     *
     * @return array{user_id: string, tenant_id: string, credential_version: int}|null null for an
     *         unknown, expired or already used link
     */
    public function claim(#[\SensitiveParameter] string $token, \DateTimeImmutable $now): ?array
    {
        if ($token === '') {
            return null;
        }

        $adapter = $this->orm()->getQueryAdapter();
        $hash = self::hash($token);

        $claimed = $adapter->execute(sprintf(
            'UPDATE %s SET used_at = :now WHERE token_hash = :token_hash AND used_at IS NULL AND expires_at > :now_check',
            SqlIdentifier::quote(self::TABLE),
        ), [
            'now'        => $now->format(self::DATETIME),
            'now_check'  => $now->format(self::DATETIME),
            'token_hash' => $hash,
        ]);
        if ($claimed->rowCount !== 1) {
            return null;
        }

        $row = $adapter->execute(sprintf(
            'SELECT user_id, tenant_id, credential_version FROM %s WHERE token_hash = :token_hash',
            SqlIdentifier::quote(self::TABLE),
        ), ['token_hash' => $hash])->fetchOne();

        return $row === null ? null : [
            'user_id' => (string) $row['user_id'],
            'tenant_id' => (string) $row['tenant_id'],
            'credential_version' => (int) ($row['credential_version'] ?? 0),
        ];
    }

    /** Use up every live link of this account: the password they would reset has just changed. */
    public function revokeAll(string $userId, \DateTimeImmutable $now): int
    {
        return $this->orm()->getQueryAdapter()->execute(sprintf(
            'UPDATE %s SET used_at = :now WHERE user_id = :user_id AND used_at IS NULL',
            SqlIdentifier::quote(self::TABLE),
        ), [
            'now'     => $now->format(self::DATETIME),
            'user_id' => $userId,
        ])->rowCount;
    }

    /**
     * Delete links that can no longer be used and no longer count towards the
     * request limit: expired before $before.
     */
    public function purgeExpiredBefore(\DateTimeImmutable $before): int
    {
        return $this->orm()->getQueryAdapter()->execute(sprintf(
            'DELETE FROM %s WHERE expires_at < :before',
            SqlIdentifier::quote(self::TABLE),
        ), ['before' => $before->format(self::DATETIME)])->rowCount;
    }

    private static function hash(#[\SensitiveParameter] string $token): string
    {
        return hash('sha256', $token);
    }

    private function orm(): OrmManager
    {
        return $this->orm ?? throw new \RuntimeException('OrmManager not injected into ' . self::class . '.');
    }
}
