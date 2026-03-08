<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformFileResource;

interface PlatformFileRepositoryInterface
{
    public function findById(string $id): ?PlatformFileResource;

    public function save(PlatformFileResource $resource): void;

    public function delete(PlatformFileResource $resource): void;
}
