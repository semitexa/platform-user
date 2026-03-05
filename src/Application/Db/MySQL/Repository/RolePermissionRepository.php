<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RolePermissionResource;

class RolePermissionRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return RolePermissionResource::class;
    }

    /**
     * @return list<RolePermissionResource>
     */
    public function findByRoleId(string $roleId): array
    {
        if (strlen($roleId) === 36 && str_contains($roleId, '-')) {
            $roleId = Uuid7::toBytes($roleId);
        }
        $sql = $this->select()->where('role_id', '=', $roleId)->buildSql();
        $rows = $this->getAdapter()->execute($sql, ['role_id' => $roleId])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, RolePermissionResource::class);
        }
        return $resources;
    }

    public function deleteByRoleId(string $roleId): void
    {
        if (strlen($roleId) === 36 && str_contains($roleId, '-')) {
            $roleId = Uuid7::toBytes($roleId);
        }
        $table = $this->getTableName();
        $this->getAdapter()->execute("DELETE FROM `{$table}` WHERE `role_id` = ?", [$roleId]);
    }
}
