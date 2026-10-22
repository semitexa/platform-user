<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Auth\Domain\Contract\UserProviderInterface;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\SatisfiesServiceContract;
use Semitexa\Core\Auth\AuthenticatableInterface;
use Semitexa\Platform\User\Auth\UserPrincipal;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;

/**
 * Re-hydrates the signed-in identity on every request.
 *
 * This is the piece `semitexa/auth` has always declared and never shipped: with
 * it installed, SessionAuthHandler stops being inert and the whole session-auth
 * chain works without an application writing any of it.
 *
 * A disabled or deleted account resolves to null, which SessionAuthHandler turns
 * into a cleared session — so revoking access takes effect on the next request,
 * not at the next sign-in.
 */
#[SatisfiesServiceContract(of: UserProviderInterface::class)]
final class PlatformUserProvider implements UserProviderInterface
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    public function findById(string $id): ?AuthenticatableInterface
    {
        if (!isset($this->users) || trim($id) === '') {
            return null;
        }

        $user = $this->users->findById($id);
        // Status, not the lockout: a lockout stops new sign-ins, and ending
        // the sessions too let anybody sign a victim out everywhere by
        // failing five times on their address.
        if ($user === null || !$user->getStatus()->canAuthenticate()) {
            return null;
        }

        return new UserPrincipal($user);
    }
}
