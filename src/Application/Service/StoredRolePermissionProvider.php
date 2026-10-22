<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\SatisfiesServiceContract;
use Semitexa\Core\Attribute\WorkerState;
use Semitexa\Core\Log\FallbackErrorLogger;
use Semitexa\Core\Tenant\TenantContextAccess;
use Semitexa\Core\Tenant\TenantContextStoreInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\SiteAccess;
use Semitexa\Rbac\Domain\Contract\PermissionProviderInterface;

/**
 * What the user's stored roles grant (RoleStore), for semitexa/rbac, on the
 * site the request is for: the shared roles and that site's own, never
 * another site's — one session can reach several tenants (a path or query
 * tenant, a cookie on the parent domain). A user bound to a site holds
 * nothing anywhere else, its shared roles included; an owner (no site) holds
 * the shared roles everywhere. On the root site, where data is not filtered
 * by tenant, the shared roles only, and only for an owner. With no tenancy at all (no
 * context: a single-site install, the CLI), the user's own site. Only a
 * platform-user account has stored roles. Providers are additive.
 */
#[SatisfiesServiceContract(of: PermissionProviderInterface::class)]
final class StoredRolePermissionProvider implements PermissionProviderInterface
{
    #[InjectAsReadonly]
    protected RoleStore $roles;

    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    #[InjectAsReadonly(optional: true)]
    protected TenantContextStoreInterface $tenants;

    #[WorkerState('Whether this worker already logged that the stored roles could not be read.')]
    private static bool $reported = false;

    /** @return list<string> */
    public function getPermissionsForUser(string $userId): array
    {
        // A platform-user id is a UUID; another sign-in method's id
        // (google:…, a project's own) is not looked up at all.
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $userId) !== 1) {
            return [];
        }
        $context = isset($this->tenants) ? $this->tenants->tryGet() : null;
        try {
            $user = $this->users->findById($userId);
            if ($user === null) {
                return [];
            }
            if ($context === null) {
                $tenantId = $user->getTenantId();
            } else {
                $tenantId = TenantContextAccess::isDefault($context) ? '' : (TenantContextAccess::tenantId($context) ?? '');
                // A site's user holds nothing on another site, nor on the
                // root site where data is not filtered by tenant: a shared
                // role given to site A's editor applied on site B (a path or
                // query tenant), and on the root site to every site's data.
                if (!SiteAccess::allows($user, $context)) {
                    return [];
                }
            }

            return $this->roles->permissionsOf($userId, $tenantId);
        } catch (\Exception $e) {
            // The tables missing after an upgrade without orm:sync must not
            // turn every permission check into a 500: no stored grant, said
            // once per worker. An Error (a bug) is not swallowed.
            if (!self::$reported) {
                self::$reported = true;
                FallbackErrorLogger::log('Stored roles could not be read; granting none of them (run bin/semitexa orm:sync?)', [
                    'error' => $e->getMessage(),
                ]);
            }

            return [];
        }
    }
}
