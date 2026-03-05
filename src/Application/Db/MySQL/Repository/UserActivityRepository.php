<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserActivityResource;

class UserActivityRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return UserActivityResource::class;
    }

    /**
     * @return list<UserActivityResource>
     */
    public function findByUserId(string $userId, int $limit = 50): array
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        $table = $this->getTableName();
        $sql = "SELECT * FROM `{$table}` WHERE `user_id` = ? ORDER BY `created_at` DESC LIMIT {$limit}";
        $rows = $this->getAdapter()->execute($sql, [$userId])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, UserActivityResource::class);
        }
        return $resources;
    }

    public function getLastLoginForUser(string $userId): ?UserActivityResource
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        return $this->select()
            ->where('user_id', '=', $userId)
            ->where('action', '=', 'login')
            ->fetchOneAsResource();
    }
}
