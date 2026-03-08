<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Domain\Model\UserActivity;

interface UserActivityRepositoryInterface
{
    /** @return list<UserActivity> */
    public function findByUserId(string $userId, int $limit = 50): array;

    public function getLastLoginForUser(string $userId): ?UserActivity;

    public function record(string $userId, string $action, ?string $ipAddress = null, ?string $userAgent = null): void;
}
