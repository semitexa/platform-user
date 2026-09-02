<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Enum;

/**
 * Whether an identity may still authenticate.
 *
 * Disabling is deliberately not deletion: an admin who leaves keeps their row,
 * so the audit trail of what they did still resolves to a name.
 */
enum UserStatus: string
{
    case Active = 'active';
    case Disabled = 'disabled';

    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
