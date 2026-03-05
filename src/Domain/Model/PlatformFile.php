<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class PlatformFile
{
    public function __construct(
        public string $id,
        public string $originalName,
        public string $mimeType,
        public int $size,
        public string $storagePath,
        public string $hash,
        public string $uploadedBy,
    ) {}
}
