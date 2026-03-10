<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Domain\Model\PlatformFile;

interface PlatformFileRepositoryInterface
{
    public function findById(string $id): ?PlatformFile;

    public function save(PlatformFile $file): void;

    public function delete(PlatformFile $file): void;
}
