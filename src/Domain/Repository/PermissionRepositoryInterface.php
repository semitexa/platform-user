<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PermissionResource;
use Semitexa\Platform\User\Domain\Model\Permission;

interface PermissionRepositoryInterface
{
    /** @return list<Permission> */
    public function findAll(int $limit = 1000): array;

    /** @return list<Permission> */
    public function findByGroup(string $groupKey): array;

    public function findBySlug(string $slug): ?PermissionResource;

    public function save(PermissionResource $resource): void;

    public function delete(PermissionResource $resource): void;
}
