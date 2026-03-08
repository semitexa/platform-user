<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\RoleResource;
use Semitexa\Platform\User\Domain\Model\Role;

interface RoleRepositoryInterface
{
    public function findById(string $id): ?RoleResource;

    /** @return list<Role> */
    public function findAll(int $limit = 100): array;

    public function findBySlug(string $slug): ?RoleResource;

    public function save(RoleResource $resource): void;

    public function delete(RoleResource $resource): void;
}
