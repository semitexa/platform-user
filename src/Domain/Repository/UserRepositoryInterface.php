<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;

interface UserRepositoryInterface
{
    public function findById(string $id): ?object;

    public function findByEmail(string $email): ?PlatformUserResource;

    /**
     * @return list<object>
     */
    public function findAll(int $limit = 100): array;

    public function save(object $resource): void;

    public function delete(object $resource): void;
}
