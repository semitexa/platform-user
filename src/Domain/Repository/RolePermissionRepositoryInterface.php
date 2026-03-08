<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\RolePermissionResource;

interface RolePermissionRepositoryInterface
{
    /** @return list<RolePermissionResource> */
    public function findByRoleId(string $roleId): array;

    public function deleteByRoleId(string $roleId): void;

    public function save(RolePermissionResource $resource): void;

    public function delete(RolePermissionResource $resource): void;
}
