<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Platform\User\Domain\Model\PlatformFile;

interface FileStorageServiceInterface
{
    public function upload(string $contents, string $originalName, string $mimeType, string $uploadedBy): PlatformFile;

    public function findById(string $id): ?PlatformFile;

    public function getContents(PlatformFile $file): ?string;

    public function delete(PlatformFile $file): void;
}
