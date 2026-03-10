<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Domain\Model\Permission;

interface PermissionRepositoryInterface
{
    /** @return list<Permission> */
    public function findAll(?int $limit = null): array;

    /** @return list<Permission> */
    public function findByGroup(string $groupKey): array;

    public function findBySlug(string $slug): ?Permission;

    /** @param list<string> $ids
     *  @return list<Permission>
     */
    public function findByIds(array $ids): array;
}
