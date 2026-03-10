<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RoleResource;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[SatisfiesRepositoryContract(of: RoleRepositoryInterface::class)]
class RoleRepository extends AbstractRepository implements RoleRepositoryInterface
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

    public function findAll(int $limit = 100): array
    {
        return $this->select()->limit($limit)->fetchAll();
    }

    public function findBySlug(string $slug): ?RoleResource
    {
        return $this->select()
            ->where('slug', '=', $slug)
            ->fetchOneAsResource();
    }
}
