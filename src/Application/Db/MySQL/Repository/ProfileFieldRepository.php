<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Hydration\Hydrator;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileFieldResource;

class ProfileFieldRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return ProfileFieldResource::class;
    }

    public function findById(int|string $id): ?ProfileFieldResource
    {
        if (is_string($id) && strlen($id) === 36 && str_contains($id, '-')) {
            $id = Uuid7::toBytes($id);
        }
        return $this->select()
            ->where($this->getPkColumn(), '=', $id)
            ->fetchOneAsResource();
    }

    /**
     * @return list<ProfileFieldResource>
     */
    public function findByTenant(): array
    {
        $sql = $this->select()->buildSql();
        $rows = $this->getAdapter()->execute($sql, [])->rows;

        $hydrator = new Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, ProfileFieldResource::class);
        }
        return $resources;
    }

    public function findBySlug(string $slug): ?ProfileFieldResource
    {
        return $this->select()
            ->where('slug', '=', $slug)
            ->fetchOneAsResource();
    }
}
