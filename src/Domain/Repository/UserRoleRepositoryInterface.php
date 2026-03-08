<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\UserRoleResource;

interface UserRoleRepositoryInterface
{
    /** @return list<UserRoleResource> */
    public function findByUserId(string $userId): array;

    public function findByUserAndRole(string $userId, string $roleId): ?UserRoleResource;

    public function deleteByUserId(string $userId): void;

    public function save(UserRoleResource $resource): void;

    public function delete(UserRoleResource $resource): void;
}
