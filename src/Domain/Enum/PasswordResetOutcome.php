<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Enum;

/** What a reset attempt did. Only Reset changed the password. */
enum PasswordResetOutcome: string
{
    case Reset = 'reset';

    /**
     * Unknown, expired or already used, or its account is gone or disabled.
     * Told apart nowhere: each says the same to whoever holds the link.
     */
    case InvalidToken = 'invalid_token';

    /** The new password was refused; the link was not used up. */
    case PasswordRejected = 'password_rejected';
}
