<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;

class PlatformUserRepository extends AbstractRepository
{
    protected function getResourceClass(): string
    {
        return PlatformUserResource::class;
    }

    public function findById(int|string $id): ?PlatformUserResource
    {
        if (is_string($id) && strlen($id) === 36 && str_contains($id, '-')) {
            $id = \Semitexa\Orm\Uuid\Uuid7::toBytes($id);
        }
        return $this->select()
            ->where($this->getPkColumn(), '=', $id)
            ->fetchOneAsResource();
    }

    public function findByEmail(string $email): ?PlatformUserResource
    {
        /** @var PlatformUserResource|null */
        return $this->select()
            ->where('email', '=', $email)
            ->fetchOneAsResource();
    }

    public function findAll(int $limit = 100): array
    {
        $sql = $this->select()->limit($limit)->buildSql();
        $adapter = $this->getAdapter();
        $rows = $adapter->execute($sql, [])->rows;
        
        $hydrator = new \Semitexa\Orm\Hydration\Hydrator();
        $resources = [];
        foreach ($rows as $row) {
            $resources[] = $hydrator->hydrate($row, PlatformUserResource::class);
        }
        return $resources;
    }
}
