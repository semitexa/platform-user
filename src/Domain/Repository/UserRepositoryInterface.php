<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Domain\Model\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?PlatformUserResource;

    public function findByEmail(string $email): ?PlatformUserResource;

    /**
     * @return list<User>
     */
    public function findAll(int $limit = 100): array;

    public function save(PlatformUserResource $resource): void;

    public function delete(PlatformUserResource $resource): void;

    /**
     * @return list<User>
     */
    public function search(string $term, int $limit = 50): array;
}
