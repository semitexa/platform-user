<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Auth\Contract\UserProviderInterface;
use Semitexa\Core\Attributes\AsServiceContract;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthenticatableInterface;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsServiceContract(of: UserProviderInterface::class)]
class PlatformUserProvider implements UserProviderInterface
{
    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function findById(string $id): ?AuthenticatableInterface
    {
        try {
            $user = $this->userRepo->findById($id);
        } catch (\Throwable $e) {
            \Semitexa\Core\Debug\SessionDebugLog::log('PlatformUserProvider.findById.ERROR', [
                'id' => $id,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);
            return null;
        }

        \Semitexa\Core\Debug\SessionDebugLog::log('PlatformUserProvider.findById', [
            'id' => $id,
            'found' => $user !== null,
            'repo_class' => get_class($this->userRepo),
        ]);

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
