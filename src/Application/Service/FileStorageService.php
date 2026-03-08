<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\SatisfiesServiceContract;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformFileResource;
use Semitexa\Platform\User\Domain\Repository\PlatformFileRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\FileStorageServiceInterface;
use Semitexa\Storage\Contract\StorageDriverInterface;

#[SatisfiesServiceContract(of: FileStorageServiceInterface::class)]
final class FileStorageService implements FileStorageServiceInterface
{
    #[InjectAsReadonly]
    protected StorageDriverInterface $storage;

    #[InjectAsReadonly]
    protected PlatformFileRepositoryInterface $fileRepo;

    public function upload(string $contents, string $originalName, string $mimeType, string $uploadedBy): PlatformFileResource
    {
        $hash = hash('sha256', $contents);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
        $uuid = Uuid7::generate();
        $uuidHex = bin2hex($uuid);
        $storagePath = $uuidHex . '.' . $ext;

        $this->storage->put($storagePath, $contents, $mimeType);

        $file = new PlatformFileResource();
        $file->id = $uuid;
        $file->original_name = $originalName;
        $file->mime_type = $mimeType;
        $file->size = strlen($contents);
        $file->storage_path = $storagePath;
        $file->hash = $hash;
        $file->uploaded_by = strlen($uploadedBy) === 36 && str_contains($uploadedBy, '-') ? Uuid7::toBytes($uploadedBy) : $uploadedBy;
        $this->fileRepo->save($file);

        return $file;
    }

    public function findById(string $id): ?PlatformFileResource
    {
        return $this->fileRepo->findById($id);
    }

    public function getContents(PlatformFileResource $file): ?string
    {
        return $this->storage->get($file->storage_path);
    }

    public function delete(PlatformFileResource $file): void
    {
        $this->storage->delete($file->storage_path);
        $this->fileRepo->delete($file);
    }
}
