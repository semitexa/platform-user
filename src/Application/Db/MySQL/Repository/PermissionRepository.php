<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PermissionResource;
use Semitexa\Platform\User\Domain\Model\Permission;
use Semitexa\Platform\User\Domain\Repository\PermissionRepositoryInterface;

#[SatisfiesRepositoryContract(of: PermissionRepositoryInterface::class)]
class PermissionRepository extends AbstractRepository implements PermissionRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return PermissionResource::class;
    }

    public function findAll(?int $limit = null): array
    {
        $query = $this->select();
        if ($limit !== null) {
            $query->limit($limit);
        }
        return $query->fetchAll();
    }

    public function findByGroup(string $groupKey): array
    {
        return $this->select()
            ->where('group_key', '=', $groupKey)
            ->fetchAll();
    }

    public function findBySlug(string $slug): ?Permission
    {
        return $this->select()
            ->where('slug', '=', $slug)
            ->fetchOne();
    }

    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $normalized = array_values(array_unique(array_map(static function (string $id): string {
            if (strlen($id) === 36 && str_contains($id, '-')) {
                return Uuid7::toBytes($id);
            }

            return $id;
        }, $ids)));

        return $this->select()
            ->whereIn('id', $normalized)
            ->fetchAll();
    }
}
