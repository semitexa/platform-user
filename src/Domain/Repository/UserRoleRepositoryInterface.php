<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Domain\Model\UserRole;

interface UserRoleRepositoryInterface
{
    /** @return list<UserRole> */
    public function findByUserId(string $userId): array;

    public function findByUserAndRole(string $userId, string $roleId): ?UserRole;

    public function deleteByUserId(string $userId): void;

    public function save(object $entity): void;

    public function delete(UserRole $role): void;
}
