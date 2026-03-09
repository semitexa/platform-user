<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RolePermissionResource;
use Semitexa\Platform\User\Domain\Repository\RolePermissionRepositoryInterface;

#[SatisfiesRepositoryContract(of: RolePermissionRepositoryInterface::class)]
class RolePermissionRepository extends AbstractRepository implements RolePermissionRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return RolePermissionResource::class;
    }

    private function normalizeUuid(string $id): string
    {
        if (strlen($id) === 36 && str_contains($id, '-')) {
            return Uuid7::toBytes($id);
        }
        return $id;
    }

    public function findByRoleId(string $roleId): array
    {
        return $this->select()
            ->where('role_id', '=', $this->normalizeUuid($roleId))
            ->fetchAll();
    }

    public function deleteByRoleId(string $roleId): void
    {
        $table = $this->getTableName();
        $this->getAdapter()->execute("DELETE FROM `{$table}` WHERE `role_id` = ?", [$this->normalizeUuid($roleId)]);
    }
}
