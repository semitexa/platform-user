<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;

class ProfileValueRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return ProfileValueResource::class;
    }

    /**
     * @return list<ProfileValueResource>
     */
    public function findByUserId(string $userId): array
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        $table = $this->getTableName();
        $rows = $this->getAdapter()->execute(
            "SELECT * FROM `{$table}` WHERE `user_id` = ?",
            [$userId],
        )->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, ProfileValueResource::class);
        }
        return $resources;
    }

    public function findByUserAndField(string $userId, string $fieldId): ?ProfileValueResource
    {
        if (strlen($userId) === 36 && str_contains($userId, '-')) {
            $userId = Uuid7::toBytes($userId);
        }
        if (strlen($fieldId) === 36 && str_contains($fieldId, '-')) {
            $fieldId = Uuid7::toBytes($fieldId);
        }
        return $this->select()
            ->where('user_id', '=', $userId)
            ->where('field_id', '=', $fieldId)
            ->fetchOneAsResource();
    }

    public function deleteByFieldId(string $fieldId): void
    {
        if (strlen($fieldId) === 36 && str_contains($fieldId, '-')) {
            $fieldId = Uuid7::toBytes($fieldId);
        }
        $table = $this->getTableName();
        $this->getAdapter()->execute("DELETE FROM `{$table}` WHERE `field_id` = ?", [$fieldId]);
    }
}
