<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformFileResource;
use Semitexa\Platform\User\Domain\Repository\PlatformFileRepositoryInterface;

#[SatisfiesRepositoryContract(of: PlatformFileRepositoryInterface::class)]
class PlatformFileRepository extends AbstractRepository implements PlatformFileRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return PlatformFileResource::class;
    }

    public function findById(int|string $id): ?PlatformFileResource
    {
        if (is_string($id) && strlen($id) === 36 && str_contains($id, '-')) {
            $id = Uuid7::toBytes($id);
        }
        return $this->select()
            ->where($this->getPkColumn(), '=', $id)
            ->fetchOneAsResource();
    }
}
