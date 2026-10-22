<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Auth;

use Semitexa\Auth\Domain\Contract\CredentialStampedInterface;
use Semitexa\Core\Auth\AuthenticatableInterface;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * What the pipeline sees once someone is signed in.
 *
 * Handlers reach the tenant and role through this rather than re-reading the
 * database: `$context->authResult?->user instanceof UserPrincipal`.
 */
final readonly class UserPrincipal implements AuthenticatableInterface, CredentialStampedInterface
{
    public function __construct(
        public PlatformUser $user,
    ) {}

    public function getId(): string
    {
        return $this->user->getId();
    }

    public function getAuthIdentifierName(): string
    {
        return 'platform_user_id';
    }

    public function getAuthIdentifier(): string
    {
        return $this->user->getId();
    }

    /** Empty string = bound to no single tenant (owner). */
    public function getTenantId(): string
    {
        return $this->user->getTenantId();
    }

    public function isOwner(): bool
    {
        return $this->user->isOwner();
    }

    /** Raised by every new password: a session signed in with an older one ends (SessionAuthHandler). */
    public function credentialStamp(): string
    {
        return (string) $this->user->getCredentialVersion();
    }

    public function getDisplayName(): string
    {
        return $this->user->getDisplayName();
    }
}
