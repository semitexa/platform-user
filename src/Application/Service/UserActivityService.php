<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\AsServiceContract;
use Semitexa\Orm\OrmManager;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserActivityResource;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\UserActivityRepository;
use Semitexa\Platform\User\Domain\Service\UserActivityServiceInterface;

#[AsServiceContract(of: UserActivityServiceInterface::class)]
final class UserActivityService implements UserActivityServiceInterface
{
    /** @return list<UserActivityResource> */
    public function findByUserId(string $userId, int $limit = 50): array
    {
        return OrmManager::run(function (OrmManager $orm) use ($userId, $limit) {
            $repo = new UserActivityRepository($orm->getAdapter());
            return $repo->findByUserId($userId, $limit);
        });
    }

    public function getLastLoginForUser(string $userId): ?UserActivityResource
    {
        return OrmManager::run(function (OrmManager $orm) use ($userId) {
            $repo = new UserActivityRepository($orm->getAdapter());
            return $repo->getLastLoginForUser($userId);
        });
    }

    public function record(string $userId, string $action, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        OrmManager::run(function (OrmManager $orm) use ($userId, $action, $ipAddress, $userAgent) {
            $repo = new UserActivityRepository($orm->getAdapter());
            $activity = new UserActivityResource();
            $activity->user_id = strlen($userId) === 36 && str_contains($userId, '-') ? Uuid7::toBytes($userId) : $userId;
            $activity->action = $action;
            $activity->ip_address = $ipAddress;
            $activity->user_agent = $userAgent;
            $activity->created_at = new \DateTimeImmutable();
            $repo->save($activity);
        });
    }
}
