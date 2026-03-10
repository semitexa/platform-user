<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attributes\SatisfiesServiceContract;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Domain\Model\PlatformFile;
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

    public function upload(string $contents, string $originalName, string $mimeType, string $uploadedBy): PlatformFile
    {
        $hash = hash('sha256', $contents);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'bin';
        $uuid = Uuid7::generate();
        $uuidHex = bin2hex($uuid);
        $storagePath = $uuidHex . '.' . $ext;

        $this->storage->put($storagePath, $contents, $mimeType);

        $file = new PlatformFile(
            id: $uuid,
            originalName: $originalName,
            mimeType: $mimeType,
            size: strlen($contents),
            storagePath: $storagePath,
            hash: $hash,
            uploadedBy: $uploadedBy,
        );

        try {
            $this->fileRepo->save($file);
        } catch (\Throwable $e) {
            try {
                $this->storage->delete($storagePath);
            } catch (\Throwable $rollbackError) {
                throw new \RuntimeException('Storage rollback failed after save error', 0, $e);
            }
            throw $e;
        }

        return $file;
    }

    public function findById(string $id): ?PlatformFile
    {
        return $this->fileRepo->findById($id);
    }

    public function getContents(PlatformFile $file): ?string
    {
        return $this->storage->get($file->storagePath);
    }

    public function delete(PlatformFile $file): void
    {
        $this->fileRepo->delete($file);

        try {
            $this->storage->delete($file->storagePath);
        } catch (\Throwable $e) {
            try {
                $this->fileRepo->save($file);
            } catch (\Throwable $rollbackError) {
                throw new \RuntimeException('DB rollback failed after storage delete error', 0, $e);
            }
            throw $e;
        }
    }
}
