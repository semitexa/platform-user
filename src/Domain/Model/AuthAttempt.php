<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

use Semitexa\Platform\User\Domain\Enum\AuthFailure;

/**
 * The outcome of one sign-in attempt: either a user, or a reason there is none.
 */
final readonly class AuthAttempt
{
    private function __construct(
        public bool $success,
        public ?PlatformUser $user = null,
        public ?AuthFailure $failure = null,
        public ?\DateTimeImmutable $lockedUntil = null,
    ) {}

    public static function granted(PlatformUser $user): self
    {
        return new self(success: true, user: $user);
    }

    public static function denied(AuthFailure $failure, ?\DateTimeImmutable $lockedUntil = null): self
    {
        return new self(success: false, failure: $failure, lockedUntil: $lockedUntil);
    }
}
