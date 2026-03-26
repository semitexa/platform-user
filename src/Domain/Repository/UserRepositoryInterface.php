<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Domain\Model\User;

interface UserRepositoryInterface
{
    /** Returns resource for mutation (save/delete). */
    public function findById(string $id): ?PlatformUserResource;

    /** Returns resource for mutation (save/delete). */
    public function findByEmail(string $email): ?PlatformUserResource;

    /** @return list<User> Domain objects for read-only listing. */
    public function findAll(int $limit = 100): array;

    public function save(object $entity): void;

    public function delete(PlatformUserResource $resource): void;

    /** @return list<User> Domain objects for read-only listing. */
    public function search(string $term, int $limit = 50): array;
}
