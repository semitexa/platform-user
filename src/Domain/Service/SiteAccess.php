<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Core\Tenant\TenantContextAccess;
use Semitexa\Core\Tenant\TenantContextInterface;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * Whether a user may act on the site a request is for. One rule for every
 * door: the stored roles (StoredRolePermissionProvider) and the OS console
 * (OsAdminGate) answered it apart, and the console did not answer it at all —
 * site A's admin signed in at site B's host and read and wrote B's pages.
 *
 * An owner may act anywhere — by role: an install made before user:add
 * refused an owner with a site has owners bound to one, and judged by the
 * empty site they lost their own console on the root site. Any other user may
 * act on their own site only: not on another, not on the root site, where data
 * is not filtered by tenant, and not at all without a site (a misconfigured
 * row). Without tenancy (no context: a single-site install, the CLI), the
 * user's own site is the only one there is.
 */
final class SiteAccess
{
    public static function allows(PlatformUser $user, ?TenantContextInterface $context): bool
    {
        if ($user->isOwner() || $context === null) {
            return true;
        }
        $requested = TenantContextAccess::isDefault($context) ? '' : (TenantContextAccess::tenantId($context) ?? '');

        return $user->getTenantId() !== '' && $requested === $user->getTenantId();
    }
}
