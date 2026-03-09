<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Domain\Model\RolePermission;

interface RolePermissionRepositoryInterface
{
    /** @return list<RolePermission> */
    public function findByRoleId(string $roleId): array;

    public function deleteByRoleId(string $roleId): void;

    public function save(RolePermission $rolePermission): void;

    public function delete(RolePermission $rolePermission): void;
}
