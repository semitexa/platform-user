<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Enum;

/**
 * The coarse role carried on the identity itself.
 *
 * This is the fallback ladder for installs that have no RBAC package wired:
 * three levels everyone understands, stored on the row. Where semitexa/rbac IS
 * installed it stays authoritative for fine-grained grants — this role then
 * only answers the two questions RBAC has no opinion about: may this identity
 * cross tenant boundaries (Owner), and may it administer other identities.
 */
enum UserRole: string
{
    /** Crosses every tenant boundary; the server operator. */
    case Owner = 'owner';

    /** Full rights inside one tenant, including managing that tenant's users. */
    case Admin = 'admin';

    /** Content rights inside one tenant; may not manage users. */
    case Editor = 'editor';

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    public function canManageUsers(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }
}
