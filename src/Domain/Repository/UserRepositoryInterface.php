<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;

interface UserRepositoryInterface
{
    public function findById(string $id): ?PlatformUserResource;

    public function findByEmail(string $email): ?PlatformUserResource;

    /**
     * @return list<PlatformUserResource>
     */
    public function findAll(int $limit = 100): array;

    public function save(PlatformUserResource $resource): void;

    public function delete(PlatformUserResource $resource): void;
}
