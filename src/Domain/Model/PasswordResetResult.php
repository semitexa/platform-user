<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

use Semitexa\Platform\User\Domain\Enum\PasswordResetOutcome;

final readonly class PasswordResetResult
{
    private function __construct(
        public PasswordResetOutcome $outcome,
        /** Why the password was refused, fit to show beside the field. */
        public ?string $passwordProblem = null,
    ) {}

    public static function reset(): self
    {
        return new self(PasswordResetOutcome::Reset);
    }

    public static function invalidToken(): self
    {
        return new self(PasswordResetOutcome::InvalidToken);
    }

    public static function passwordRejected(string $problem): self
    {
        return new self(PasswordResetOutcome::PasswordRejected, $problem);
    }

    public function succeeded(): bool
    {
        return $this->outcome === PasswordResetOutcome::Reset;
    }
}
