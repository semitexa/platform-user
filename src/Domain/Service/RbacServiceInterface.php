<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Platform\User\Domain\Model\Permission;
use Semitexa\Platform\User\Domain\Model\Role;

interface RbacServiceInterface
{
    /** @return list<Role> */
    public function getUserRoles(string $userId): array;

    /** @return list<Permission> */
    public function getUserPermissions(string $userId): array;

    public function userHasPermission(string $userId, string $permissionSlug): bool;

    public function assignRole(string $userId, string $roleId): void;

    public function revokeRole(string $userId, string $roleId): void;

    /** @return list<Permission> */
    public function getRolePermissions(string $roleId): array;

    /** @param list<string> $permissionIds */
    public function setRolePermissions(string $roleId, array $permissionIds): void;
}
