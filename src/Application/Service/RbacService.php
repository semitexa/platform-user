<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Attributes\SatisfiesServiceContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Domain\Model\Permission;
use Semitexa\Platform\User\Domain\Model\RolePermission;
use Semitexa\Platform\User\Domain\Model\Role;
use Semitexa\Platform\User\Domain\Model\UserRole;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\RolePermissionRepository as DbRolePermissionRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\UserRoleRepository as DbUserRoleRepository;
use Semitexa\Platform\User\Domain\Repository\PermissionRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\RolePermissionRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\UserRoleRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[SatisfiesServiceContract(of: RbacServiceInterface::class)]
final class RbacService implements RbacServiceInterface
{
    #[InjectAsReadonly]
    protected UserRoleRepositoryInterface $userRoleRepo;

    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    #[InjectAsReadonly]
    protected RolePermissionRepositoryInterface $rolePermRepo;

    #[InjectAsReadonly]
    protected PermissionRepositoryInterface $permRepo;

    /** @return list<Role> */
    public function getUserRoles(string $userId): array
    {
        $userRoles = $this->userRoleRepo->findByUserId($userId);
        $roles = [];
        foreach ($userRoles as $ur) {
            $role = $this->roleRepo->findById($ur->roleId);
            if ($role !== null) {
                $roles[] = $role->toDomain();
            }
        }
        return $roles;
    }

    /** @return list<Permission> */
    public function getUserPermissions(string $userId): array
    {
        $userRoles = $this->userRoleRepo->findByUserId($userId);
        $permissionIds = [];

        foreach ($userRoles as $ur) {
            $rolePerms = $this->rolePermRepo->findByRoleId($ur->roleId);
            foreach ($rolePerms as $rp) {
                $permissionIds[$rp->permissionId] = true;
            }
        }

        return $this->permRepo->findByIds(array_keys($permissionIds));
    }

    public function userHasPermission(string $userId, string $permissionSlug): bool
    {
        $permissions = $this->getUserPermissions($userId);
        foreach ($permissions as $p) {
            if ($p->slug === $permissionSlug) {
                return true;
            }
        }
        return false;
    }

    public function assignRole(string $userId, string $roleId): void
    {
        OrmManager::run(function (OrmManager $orm) use ($userId, $roleId): void {
            $orm->getTransactionManager()->run(function ($adapter) use ($userId, $roleId): void {
                $repo = new DbUserRoleRepository($adapter);
                $existing = $repo->findByUserAndRole($userId, $roleId);
                if ($existing !== null) {
                    return;
                }

                try {
                    $repo->save(new UserRole(
                        id: Uuid7::generate(),
                        userId: $userId,
                        roleId: $roleId,
                    ));
                } catch (\Throwable $e) {
                    if (!$this->isDuplicateKey($e)) {
                        throw $e;
                    }
                }
            });
        });
    }

    public function revokeRole(string $userId, string $roleId): void
    {
        $existing = $this->userRoleRepo->findByUserAndRole($userId, $roleId);
        if ($existing !== null) {
            $this->userRoleRepo->delete($existing);
        }
    }

    /** @return list<Permission> */
    public function getRolePermissions(string $roleId): array
    {
        $rolePerms = $this->rolePermRepo->findByRoleId($roleId);
        $permissionIds = [];
        foreach ($rolePerms as $rp) {
            $permissionIds[$rp->permissionId] = true;
        }

        return $this->permRepo->findByIds(array_keys($permissionIds));
    }

    /** @param list<string> $permissionIds */
    public function setRolePermissions(string $roleId, array $permissionIds): void
    {
        OrmManager::run(function (OrmManager $orm) use ($roleId, $permissionIds): void {
            $orm->getTransactionManager()->run(function ($adapter) use ($roleId, $permissionIds): void {
                $repo = new DbRolePermissionRepository($adapter);
                $repo->deleteByRoleId($roleId);

                foreach ($permissionIds as $permId) {
                    $repo->save(new RolePermission(
                        id: Uuid7::generate(),
                        roleId: $roleId,
                        permissionId: $permId,
                    ));
                }
            });
        });
    }

    private function isDuplicateKey(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'duplicate')
            || str_contains($message, '1062')
            || str_contains($message, '23000');
    }
}
