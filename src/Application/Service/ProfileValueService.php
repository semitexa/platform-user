<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\ProfileValueRepository;
use Semitexa\Platform\User\Domain\Repository\ProfileValueRepositoryInterface;

#[SatisfiesRepositoryContract(of: ProfileValueRepositoryInterface::class)]
final class ProfileValueService implements ProfileValueRepositoryInterface
{
    /** @return list<ProfileValueResource> */
    public function findByUserId(string $userId): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($userId) {
            $repo = new ProfileValueRepository($orm->getAdapter());
            return $repo->findByUserId($userId);
        });
    }

    public function findByUserAndField(string $userId, string $fieldId): ?ProfileValueResource
    {
        return OrmManager::run(function (OrmManager $orm) use ($userId, $fieldId) {
            $repo = new ProfileValueRepository($orm->getAdapter());
            return $repo->findByUserAndField($userId, $fieldId);
        });
    }

    public function save(ProfileValueResource $resource): void
    {
        OrmManager::run(function (OrmManager $orm) use ($resource) {
            $repo = new ProfileValueRepository($orm->getAdapter());
            $repo->save($resource);
        });
    }

    public function deleteByFieldId(string $fieldId): void
    {
        OrmManager::run(function (OrmManager $orm) use ($fieldId) {
            $repo = new ProfileValueRepository($orm->getAdapter());
            $repo->deleteByFieldId($fieldId);
        });
    }
}
