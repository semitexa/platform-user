<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformFileResource;

interface FileStorageServiceInterface
{
    public function upload(string $contents, string $originalName, string $mimeType, string $uploadedBy): PlatformFileResource;

    public function findById(string $id): ?PlatformFileResource;

    public function getContents(PlatformFileResource $file): ?string;

    public function delete(PlatformFileResource $file): void;
}
