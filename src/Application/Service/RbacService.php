<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\AsServiceContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PermissionResource;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RolePermissionResource;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserRoleResource;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PermissionRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\RolePermissionRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\RoleRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\UserRoleRepository;
use Semitexa\Platform\User\Domain\Model\Permission;
use Semitexa\Platform\User\Domain\Model\Role;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsServiceContract(of: RbacServiceInterface::class)]
final class RbacService implements RbacServiceInterface
{
    /** @return list<Role> */
    public function getUserRoles(string $userId): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($userId) {
            $userRoleRepo = new UserRoleRepository($orm->getAdapter());
            $roleRepo = new RoleRepository($orm->getAdapter());

            $userRoles = $userRoleRepo->findByUserId($userId);
            $roles = [];
            foreach ($userRoles as $ur) {
                $role = $roleRepo->findById($ur->role_id);
                if ($role !== null) {
                    $roles[] = $role->toDomain();
                }
            }
            return $roles;
        });
    }

    /** @return list<Permission> */
    public function getUserPermissions(string $userId): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($userId) {
            $userRoleRepo = new UserRoleRepository($orm->getAdapter());
            $rolePermRepo = new RolePermissionRepository($orm->getAdapter());
            $permRepo = new PermissionRepository($orm->getAdapter());

            $userRoles = $userRoleRepo->findByUserId($userId);
            $permissionIds = [];

            foreach ($userRoles as $ur) {
                $rolePerms = $rolePermRepo->findByRoleId($ur->role_id);
                foreach ($rolePerms as $rp) {
                    $permissionIds[$rp->permission_id] = true;
                }
            }

            $allPerms = $permRepo->findAll();
            $result = [];
            foreach ($allPerms as $perm) {
                if (isset($permissionIds[$perm->id])) {
                    $result[] = $perm->toDomain();
                }
            }
            return $result;
        });
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
        OrmManager::run(function (OrmManager $orm) use ($userId, $roleId) {
            $repo = new UserRoleRepository($orm->getAdapter());
            $existing = $repo->findByUserAndRole($userId, $roleId);
            if ($existing !== null) {
                return;
            }

            $ur = new UserRoleResource();
            $ur->user_id = strlen($userId) === 36 && str_contains($userId, '-') ? Uuid7::toBytes($userId) : $userId;
            $ur->role_id = strlen($roleId) === 36 && str_contains($roleId, '-') ? Uuid7::toBytes($roleId) : $roleId;
            $repo->save($ur);
        });
    }

    public function revokeRole(string $userId, string $roleId): void
    {
        OrmManager::run(function (OrmManager $orm) use ($userId, $roleId) {
            $repo = new UserRoleRepository($orm->getAdapter());
            $existing = $repo->findByUserAndRole($userId, $roleId);
            if ($existing !== null) {
                $repo->delete($existing);
            }
        });
    }

    /** @return list<Permission> */
    public function getRolePermissions(string $roleId): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($roleId) {
            $rolePermRepo = new RolePermissionRepository($orm->getAdapter());
            $permRepo = new PermissionRepository($orm->getAdapter());

            $rolePerms = $rolePermRepo->findByRoleId($roleId);
            $permissionIds = [];
            foreach ($rolePerms as $rp) {
                $permissionIds[$rp->permission_id] = true;
            }

            $allPerms = $permRepo->findAll();
            $result = [];
            foreach ($allPerms as $perm) {
                if (isset($permissionIds[$perm->id])) {
                    $result[] = $perm->toDomain();
                }
            }
            return $result;
        });
    }

    /** @param list<string> $permissionIds */
    public function setRolePermissions(string $roleId, array $permissionIds): void
    {
        OrmManager::run(function (OrmManager $orm) use ($roleId, $permissionIds) {
            $rolePermRepo = new RolePermissionRepository($orm->getAdapter());

            $rolePermRepo->deleteByRoleId($roleId);

            $roleIdBytes = strlen($roleId) === 36 && str_contains($roleId, '-') ? Uuid7::toBytes($roleId) : $roleId;

            foreach ($permissionIds as $permId) {
                $rp = new RolePermissionResource();
                $rp->role_id = $roleIdBytes;
                $rp->permission_id = strlen($permId) === 36 && str_contains($permId, '-') ? Uuid7::toBytes($permId) : $permId;
                $rolePermRepo->save($rp);
            }
        });
    }
}
