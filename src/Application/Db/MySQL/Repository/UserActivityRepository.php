<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\UserActivityResource;
use Semitexa\Platform\User\Domain\Model\UserActivity;
use Semitexa\Platform\User\Domain\Repository\UserActivityRepositoryInterface;

#[SatisfiesRepositoryContract(of: UserActivityRepositoryInterface::class)]
class UserActivityRepository extends AbstractRepository implements UserActivityRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return UserActivityResource::class;
    }

    private function normalizeUuid(string $id): string
    {
        if (strlen($id) === 36 && str_contains($id, '-')) {
            return Uuid7::toBytes($id);
        }
        return $id;
    }

    public function findByUserId(string $userId, int $limit = 50): array
    {
        return $this->select()
            ->where('user_id', '=', $this->normalizeUuid($userId))
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    public function getLastLoginForUser(string $userId): ?UserActivity
    {
        return $this->select()
            ->where('user_id', '=', $this->normalizeUuid($userId))
            ->where('action', '=', 'login')
            ->orderBy('created_at', 'DESC')
            ->fetchOne();
    }

    public function record(string $userId, string $action, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        $activity = new UserActivityResource();
        $activity->user_id = $this->normalizeUuid($userId);
        $activity->action = $action;
        $activity->ip_address = $ipAddress;
        $activity->user_agent = $userAgent;
        $activity->created_at = new \DateTimeImmutable();
        $this->save($activity);
    }
}
