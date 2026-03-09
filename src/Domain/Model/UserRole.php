<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class UserRole
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $roleId,
    ) {}
}
