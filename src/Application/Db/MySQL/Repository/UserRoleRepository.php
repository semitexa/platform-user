<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserRoleResource;
use Semitexa\Platform\User\Domain\Repository\UserRoleRepositoryInterface;

#[SatisfiesRepositoryContract(of: UserRoleRepositoryInterface::class)]
class UserRoleRepository extends AbstractRepository implements UserRoleRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return UserRoleResource::class;
    }

    private function normalizeUuid(string $id): string
    {
        if (strlen($id) === 36 && str_contains($id, '-')) {
            return Uuid7::toBytes($id);
        }
        return $id;
    }

    /**
     * @return list<UserRoleResource>
     */
    public function findByUserId(string $userId): array
    {
        return $this->select()
            ->where('user_id', '=', $this->normalizeUuid($userId))
            ->fetchAllAsResource();
    }

    public function findByUserAndRole(string $userId, string $roleId): ?UserRoleResource
    {
        return $this->select()
            ->where('user_id', '=', $this->normalizeUuid($userId))
            ->where('role_id', '=', $this->normalizeUuid($roleId))
            ->fetchOneAsResource();
    }

    public function deleteByUserId(string $userId): void
    {
        $table = $this->getTableName();
        $this->getAdapter()->execute("DELETE FROM `{$table}` WHERE `user_id` = ?", [$this->normalizeUuid($userId)]);
    }
}
