<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[SatisfiesRepositoryContract(of: UserRepositoryInterface::class)]
final class PlatformUserService implements UserRepositoryInterface
{
    public function findById(string $id): ?PlatformUserResource
    {
        return OrmManager::run(function (OrmManager $orm) use ($id) {
            $repo = new PlatformUserRepository($orm->getAdapter());

            /** @var PlatformUserResource|null */
            return $repo->findById($id);
        });
    }

    public function findByEmail(string $email): ?PlatformUserResource
    {
        return OrmManager::run(function (OrmManager $orm) use ($email) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            return $repo->findByEmail($email);
        });
    }

    /**
     * @return list<PlatformUserResource>
     */
    public function findAll(int $limit = 100): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($limit) {
            $repo = new PlatformUserRepository($orm->getAdapter());

            /** @var list<PlatformUserResource> */
            return $repo->findAll($limit);
        });
    }

    public function save(PlatformUserResource $resource): void
    {
        OrmManager::run(function (OrmManager $orm) use ($resource) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            $repo->save($resource);
        });
    }

    public function delete(PlatformUserResource $resource): void
    {
        OrmManager::run(function (OrmManager $orm) use ($resource) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            $repo->delete($resource);
        });
    }

    /**
     * @return list<PlatformUserResource>
     */
    public function search(string $term, int $limit = 50): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($term, $limit) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            return $repo->search($term, $limit);
        });
    }
}
