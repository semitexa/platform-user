<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Platform\User\Application\Db\MySQL\Model\UserActivityResource;

interface UserActivityServiceInterface
{
    /** @return list<UserActivityResource> */
    public function findByUserId(string $userId, int $limit = 50): array;

    public function getLastLoginForUser(string $userId): ?UserActivityResource;

    public function record(string $userId, string $action, ?string $ipAddress = null, ?string $userAgent = null): void;
}
