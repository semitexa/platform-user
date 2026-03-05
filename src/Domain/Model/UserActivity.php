<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class UserActivity
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $action,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?\DateTimeImmutable $createdAt = null,
    ) {}
}
