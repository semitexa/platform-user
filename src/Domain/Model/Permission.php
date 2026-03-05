<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class Permission
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $name,
        public string $groupKey,
    ) {}
}
