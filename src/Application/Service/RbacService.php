<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Attributes\SatisfiesServiceContract;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RolePermissionResource;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserRoleResource;
use Semitexa\Platform\User\Domain\Model\Permission;
use Semitexa\Platform\User\Domain\Model\Role;
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
            $role = $this->roleRepo->findById($ur->role_id);
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
            $rolePerms = $this->rolePermRepo->findByRoleId($ur->role_id);
            foreach ($rolePerms as $rp) {
                $permissionIds[$rp->permission_id] = true;
            }
        }

        $result = [];
        foreach ($this->permRepo->findAll() as $perm) {
            if (isset($permissionIds[$perm->id])) {
                $result[] = $perm;
            }
        }
        return $result;
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
        $existing = $this->userRoleRepo->findByUserAndRole($userId, $roleId);
        if ($existing !== null) {
            return;
        }

        $ur = new UserRoleResource();
        $ur->user_id = strlen($userId) === 36 && str_contains($userId, '-') ? Uuid7::toBytes($userId) : $userId;
        $ur->role_id = strlen($roleId) === 36 && str_contains($roleId, '-') ? Uuid7::toBytes($roleId) : $roleId;
        $this->userRoleRepo->save($ur);
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
            $permissionIds[$rp->permission_id] = true;
        }

        $result = [];
        foreach ($this->permRepo->findAll() as $perm) {
            if (isset($permissionIds[$perm->id])) {
                $result[] = $perm;
            }
        }
        return $result;
    }

    /** @param list<string> $permissionIds */
    public function setRolePermissions(string $roleId, array $permissionIds): void
    {
        $this->rolePermRepo->deleteByRoleId($roleId);

        $roleIdBytes = strlen($roleId) === 36 && str_contains($roleId, '-') ? Uuid7::toBytes($roleId) : $roleId;

        foreach ($permissionIds as $permId) {
            $rp = new RolePermissionResource();
            $rp->role_id = $roleIdBytes;
            $rp->permission_id = strlen($permId) === 36 && str_contains($permId, '-') ? Uuid7::toBytes($permId) : $permId;
            $this->rolePermRepo->save($rp);
        }
    }
}
