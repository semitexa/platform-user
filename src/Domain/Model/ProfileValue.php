<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class ProfileValue
{
    public function __construct(
        public string $id,
        public string $userId,
        public string $fieldId,
        public ?string $value = null,
        public ?string $fileId = null,
    ) {}
}
