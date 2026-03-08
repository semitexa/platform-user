<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PermissionResource;
use Semitexa\Platform\User\Domain\Repository\PermissionRepositoryInterface;

#[SatisfiesRepositoryContract(of: PermissionRepositoryInterface::class)]
class PermissionRepository extends AbstractRepository implements PermissionRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return PermissionResource::class;
    }

    public function findAll(int $limit = 1000): array
    {
        return $this->select()->limit($limit)->fetchAll();
    }

    public function findByGroup(string $groupKey): array
    {
        return $this->select()
            ->where('group_key', '=', $groupKey)
            ->fetchAll();
    }

    public function findBySlug(string $slug): ?PermissionResource
    {
        return $this->select()
            ->where('slug', '=', $slug)
            ->fetchOneAsResource();
    }
}
