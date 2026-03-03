<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

class PlatformUserRepository extends AbstractRepository implements UserRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return PlatformUserResource::class;
    }

    public function findByEmail(string $email): ?PlatformUserResource
    {
        /** @var PlatformUserResource|null */
        return $this->select()
            ->where('email', '=', $email)
            ->fetchOneAsResource();
    }
}
