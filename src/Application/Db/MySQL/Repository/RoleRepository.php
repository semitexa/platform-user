<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RoleResource;

class RoleRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return RoleResource::class;
    }

    public function findById(int|string $id): ?RoleResource
    {
        if (is_string($id) && strlen($id) === 36 && str_contains($id, '-')) {
            $id = Uuid7::toBytes($id);
        }
        return $this->select()
            ->where($this->getPkColumn(), '=', $id)
            ->fetchOneAsResource();
    }

    /**
     * @return list<RoleResource>
     */
    public function findAll(int $limit = 100): array
    {
        $sql = $this->select()->limit($limit)->buildSql();
        $rows = $this->getAdapter()->execute($sql, [])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, RoleResource::class);
        }
        return $resources;
    }

    public function findBySlug(string $slug): ?RoleResource
    {
        return $this->select()
            ->where('slug', '=', $slug)
            ->fetchOneAsResource();
    }
}
