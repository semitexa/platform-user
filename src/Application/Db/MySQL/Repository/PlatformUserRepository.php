<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[SatisfiesRepositoryContract(of: UserRepositoryInterface::class)]
class PlatformUserRepository extends AbstractRepository implements UserRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return PlatformUserResource::class;
    }

    public function findById(int|string $id): ?PlatformUserResource
    {
        if (is_string($id) && strlen($id) === 36 && str_contains($id, '-')) {
            $id = Uuid7::toBytes($id);
        }
        return $this->select()
            ->where($this->getPkColumn(), '=', $id)
            ->fetchOneAsResource();
    }

    public function findByEmail(string $email): ?PlatformUserResource
    {
        return $this->select()
            ->where('email', '=', $email)
            ->fetchOneAsResource();
    }

    public function findAll(int $limit = 100): array
    {
        return $this->select()->limit($limit)->fetchAll();
    }

    public function search(string $term, int $limit = 50): array
    {
        return $this->select()
            ->whereLike('name', "%{$term}%")
            ->orWhere('email', 'LIKE', "%{$term}%")
            ->limit($limit)
            ->fetchAll();
    }
}
