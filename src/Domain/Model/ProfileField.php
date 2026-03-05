<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class ProfileField
{
    public function __construct(
        public string $id,
        public string $slug,
        public string $label,
        public string $type,
        public bool $isRequired,
        public int $sortOrder,
        public array $options,
        public bool $isVisible,
        public ?string $icon = null,
    ) {}
}
