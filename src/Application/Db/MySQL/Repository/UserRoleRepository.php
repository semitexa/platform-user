<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserRoleResource;

class UserRoleRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return UserRoleResource::class;
    }

    /**
     * @return list<UserRoleResource>
     */
    public function findByUserId(string $userId): array
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        $sql = $this->select()->where('user_id', '=', $userId)->buildSql();
        $rows = $this->getAdapter()->execute($sql, ['user_id' => $userId])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, UserRoleResource::class);
        }
        return $resources;
    }

    public function findByUserAndRole(string $userId, string $roleId): ?UserRoleResource
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        if (strlen($roleId) === 36 && str_contains($roleId, '-')) {
            $roleId = Uuid7::toBytes($roleId);
        }
        return $this->select()
            ->where('user_id', '=', $userId)
            ->where('role_id', '=', $roleId)
            ->fetchOneAsResource();
    }

    public function deleteByUserId(string $userId): void
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        $table = $this->getTableName();
        $this->getAdapter()->execute("DELETE FROM `{$table}` WHERE `user_id` = ?", [$userId]);
    }
}
