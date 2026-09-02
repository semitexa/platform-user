<?php

declare(strict_types=1);

namespace Semitexa\Platform\User;

use Semitexa\Core\Attribute\Capability;

/**
 * What this package offers, for the capability catalog.
 *
 * Nothing reads this at runtime; it exists so a project that has not installed
 * the package can still find out it exists — that audience is precisely the one
 * about to write a users table by hand.
 */
#[Capability(
    id: 'platform-user.identity',
    summary: 'People who sign in: password credentials, roles, tenant binding, and the lockout that follows repeated failures.',
    useWhen: 'Something in the project needs a human to log in — an admin console, a customer area, any session-backed page.',
    avoidWhen: 'Identity comes from elsewhere entirely — an upstream SSO, or machine credentials (semitexa/api) rather than people.',
    replaces: [
        'a users table with password_hash() called at three different call sites and verified at a fourth',
        'a failed-attempt counter that each handler increments its own way, or not at all',
        'a UserProviderInterface implementation written per project so session auth stops being inert',
    ],
    seeAlso: 'semitexa/auth',
)]
final class Capabilities
{
}
