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
use Semitexa\Rbac\Domain\Contract\PermissionProviderInterface;

/**
 * What the user's stored roles grant (RoleStore), for semitexa/rbac, on the
 * site the request is for: the shared roles and that site's own, never
 * another site's — one session can reach several tenants (a path or query
 * tenant, a cookie on the parent domain). On the root site, where data is not
 * filtered by tenant, the shared roles only. With no tenancy at all (no
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
            if ($context === null) {
                $user = $this->users->findById($userId);
                $tenantId = $user?->getTenantId();
            } else {
                $tenantId = TenantContextAccess::isDefault($context) ? '' : (TenantContextAccess::tenantId($context) ?? '');
            }
            if ($tenantId === null) {
                return [];
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
