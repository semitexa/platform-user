<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Enum;

/**
 * Why a sign-in attempt did not succeed.
 *
 * The distinction exists for the log and for rate-limiting decisions, NOT for
 * the message shown to the visitor: telling an anonymous form which half of the
 * pair was wrong turns the login into an account-enumeration oracle. Callers
 * render one neutral sentence for {@see self::NoSuchUser} and
 * {@see self::WrongPassword} alike.
 */
enum AuthFailure: string
{
    case NoSuchUser = 'no_such_user';
    case WrongPassword = 'wrong_password';
    case Disabled = 'disabled';
    case Locked = 'locked';

    /** Safe to show: the visitor cannot learn anything they could not guess. */
    public function isSafeToDisclose(): bool
    {
        return $this === self::Locked;
    }
}
