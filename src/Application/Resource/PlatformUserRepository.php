<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Resource;

use Semitexa\Orm\Repository\AbstractRepository;

class PlatformUserRepository extends AbstractRepository
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
