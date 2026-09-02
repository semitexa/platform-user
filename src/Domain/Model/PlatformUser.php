<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Enum\UserStatus;

/**
 * A person who signs in.
 *
 * The raw password is never held here — only its hash, and only long enough to
 * verify one attempt. {@see verifyPassword()} is the single place that compares,
 * so no call site is tempted to do it with `===`.
 *
 * Tenant binding is a plain string rather than a relation on purpose: tenants in
 * Semitexa are declared in the environment (TENANT_*_DOMAINS), not in a table, so
 * a foreign key would point at nothing. An empty tenant id means "not bound to
 * one site" and is only meaningful together with {@see UserRole::Owner}.
 */
final readonly class PlatformUser
{
    public function __construct(
        private string $id,
        private string $email,
        private string $passwordHash,
        private string $displayName = '',
        private string $tenantId = '',
        private UserRole $role = UserRole::Editor,
        private UserStatus $status = UserStatus::Active,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
        private ?\DateTimeImmutable $updatedAt = null,
        private ?\DateTimeImmutable $lastLoginAt = null,
        private int $failedAttempts = 0,
        private ?\DateTimeImmutable $lockedUntil = null,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getDisplayName(): string
    {
        return $this->displayName !== '' ? $this->displayName : $this->email;
    }

    /** Empty string = bound to no single tenant (see {@see isOwner()}). */
    public function getTenantId(): string
    {
        return $this->tenantId;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function getStatus(): UserStatus
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getLastLoginAt(): ?\DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function getLockedUntil(): ?\DateTimeImmutable
    {
        return $this->lockedUntil;
    }

    public function isOwner(): bool
    {
        return $this->role->isOwner();
    }

    /**
     * True while a lockout from repeated failures is still in force.
     */
    public function isLocked(?\DateTimeImmutable $now = null): bool
    {
        if ($this->lockedUntil === null) {
            return false;
        }

        return $this->lockedUntil > ($now ?? new \DateTimeImmutable());
    }

    /**
     * The two conditions that must hold before a password is even compared.
     * Kept together so a caller cannot check one and forget the other.
     */
    public function canAuthenticate(?\DateTimeImmutable $now = null): bool
    {
        return $this->status->canAuthenticate() && !$this->isLocked($now);
    }

    /**
     * Constant-time comparison of one attempt against the stored hash.
     */
    public function verifyPassword(#[\SensitiveParameter] string $rawPassword): bool
    {
        if ($this->passwordHash === '' || $rawPassword === '') {
            return false;
        }

        return password_verify($rawPassword, $this->passwordHash);
    }

    /**
     * True when the stored hash was made with weaker parameters than the ones
     * currently configured — the caller should re-hash on the next successful
     * sign-in.
     */
    /** @param array<string, mixed> $options */
    public function needsRehash(string|int|null $algorithm = PASSWORD_DEFAULT, array $options = []): bool
    {
        return password_needs_rehash($this->passwordHash, $algorithm, $options);
    }

    public function withPasswordHash(string $passwordHash): self
    {
        return $this->with(passwordHash: $passwordHash, failedAttempts: 0, clearLock: true);
    }

    public function withStatus(UserStatus $status): self
    {
        return $this->with(status: $status);
    }

    public function withRole(UserRole $role): self
    {
        return $this->with(role: $role);
    }

    public function withDisplayName(string $displayName): self
    {
        return $this->with(displayName: $displayName);
    }

    /** Records a successful sign-in and clears the failure counters. */
    public function withSuccessfulLogin(?\DateTimeImmutable $at = null): self
    {
        return $this->with(
            lastLoginAt: $at ?? new \DateTimeImmutable(),
            failedAttempts: 0,
            clearLock: true,
        );
    }

    /**
     * Records one failed attempt, locking the account once the threshold is
     * reached. Returning a new instance keeps the decision in the model rather
     * than in whichever handler happened to catch the failure.
     */
    public function withFailedLogin(int $maxAttempts, int $lockSeconds, ?\DateTimeImmutable $now = null): self
    {
        $attempts = $this->failedAttempts + 1;
        $now ??= new \DateTimeImmutable();

        return $this->with(
            failedAttempts: $attempts,
            lockedUntil: $attempts >= $maxAttempts
                ? $now->add(new \DateInterval('PT' . max(1, $lockSeconds) . 'S'))
                : $this->lockedUntil,
        );
    }

    private function with(
        ?string $passwordHash = null,
        ?string $displayName = null,
        ?UserRole $role = null,
        ?UserStatus $status = null,
        ?\DateTimeImmutable $lastLoginAt = null,
        ?int $failedAttempts = null,
        ?\DateTimeImmutable $lockedUntil = null,
        bool $clearLock = false,
    ): self {
        return new self(
            id: $this->id,
            email: $this->email,
            passwordHash: $passwordHash ?? $this->passwordHash,
            displayName: $displayName ?? $this->displayName,
            tenantId: $this->tenantId,
            role: $role ?? $this->role,
            status: $status ?? $this->status,
            createdAt: $this->createdAt,
            updatedAt: new \DateTimeImmutable(),
            lastLoginAt: $lastLoginAt ?? $this->lastLoginAt,
            failedAttempts: $failedAttempts ?? $this->failedAttempts,
            lockedUntil: $clearLock ? null : ($lockedUntil ?? $this->lockedUntil),
        );
    }
}
