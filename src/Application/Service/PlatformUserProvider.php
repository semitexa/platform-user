<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Auth\Contract\UserProviderInterface;
use Semitexa\Core\Attributes\AsServiceContract;
use Semitexa\Core\Auth\AuthenticatableInterface;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Resource\PlatformUserRepository;

#[AsServiceContract(of: UserProviderInterface::class)]
class PlatformUserProvider implements UserProviderInterface
{
    public function findById(string $id): ?AuthenticatableInterface
    {
        return OrmManager::run(function (OrmManager $orm) use ($id) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            $user = $repo->findById($id);

            if (!$user instanceof \Semitexa\Platform\User\Domain\User) {
                return null;
            }

            if (!$user->isActive) {
                return null;
            }

            return $user;
        });
    }
}
