<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PermissionResource;

class PermissionRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return PermissionResource::class;
    }

    /**
     * @return list<PermissionResource>
     */
    public function findAll(int $limit = 1000): array
    {
        $sql = $this->select()->limit($limit)->buildSql();
        $rows = $this->getAdapter()->execute($sql, [])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, PermissionResource::class);
        }
        return $resources;
    }

    /**
     * @return list<PermissionResource>
     */
    public function findByGroup(string $groupKey): array
    {
        $sql = $this->select()->where('group_key', '=', $groupKey)->buildSql();
        $rows = $this->getAdapter()->execute($sql, ['group_key' => $groupKey])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, PermissionResource::class);
        }
        return $resources;
    }

    public function findBySlug(string $slug): ?PermissionResource
    {
        return $this->select()
            ->where('slug', '=', $slug)
            ->fetchOneAsResource();
    }
}
