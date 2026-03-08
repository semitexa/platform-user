<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Auth\Contract\UserProviderInterface;
use Semitexa\Core\Attributes\SatisfiesServiceContract;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthenticatableInterface;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[SatisfiesServiceContract(of: UserProviderInterface::class)]
class PlatformUserProvider implements UserProviderInterface
{
    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function findById(string $id): ?AuthenticatableInterface
    {
        $user = $this->userRepo->findById($id);

        if ($user === null) {
            return null;
        }

        $domain = $user->toDomain();

        if (!$domain->isActive) {
            return null;
        }

        return $domain;
    }
}
