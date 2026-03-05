<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\AsServiceContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileFieldResource;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\ProfileFieldRepository;
use Semitexa\Platform\User\Domain\Service\ProfileFieldServiceInterface;

#[AsServiceContract(of: ProfileFieldServiceInterface::class)]
final class ProfileFieldService implements ProfileFieldServiceInterface
{
    /** @return list<ProfileFieldResource> */
    public function findAll(): array
    {
        return OrmManager::run(function (OrmManager $orm) {
            $repo = new ProfileFieldRepository($orm->getAdapter());
            return $repo->findByTenant();
        });
    }

    public function findById(string $id): ?ProfileFieldResource
    {
        return OrmManager::run(function (OrmManager $orm) use ($id) {
            $repo = new ProfileFieldRepository($orm->getAdapter());
            return $repo->findById($id);
        });
    }

    public function findBySlug(string $slug): ?ProfileFieldResource
    {
        return OrmManager::run(function (OrmManager $orm) use ($slug) {
            $repo = new ProfileFieldRepository($orm->getAdapter());
            return $repo->findBySlug($slug);
        });
    }

    public function save(ProfileFieldResource $resource): void
    {
        OrmManager::run(function (OrmManager $orm) use ($resource) {
            $repo = new ProfileFieldRepository($orm->getAdapter());
            $repo->save($resource);
        });
    }

    public function delete(ProfileFieldResource $resource): void
    {
        OrmManager::run(function (OrmManager $orm) use ($resource) {
            $repo = new ProfileFieldRepository($orm->getAdapter());
            $repo->delete($resource);
        });
    }
}
