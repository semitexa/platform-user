<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileFieldResource;
use Semitexa\Platform\User\Domain\Model\ProfileField;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;

#[SatisfiesRepositoryContract(of: ProfileFieldRepositoryInterface::class)]
class ProfileFieldRepository extends AbstractRepository implements ProfileFieldRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return ProfileFieldResource::class;
    }

    public function findById(int|string $id): ?ProfileField
    {
        if (is_string($id) && strlen($id) === 36 && str_contains($id, '-')) {
            $id = Uuid7::toBytes($id);
        }
        $resource = $this->select()
            ->where($this->getPkColumn(), '=', $id)
            ->fetchOneAsResource();

        return $resource?->toDomain();
    }

    public function findAll(?int $limit = null): array
    {
        $query = $this->select();
        if ($limit !== null) {
            $query->limit($limit);
        }
        return array_values(array_map(
            static fn(ProfileFieldResource $resource) => $resource->toDomain(),
            $query->fetchAll(),
        ));
    }

    public function findBySlug(string $slug): ?ProfileField
    {
        $resource = $this->select()
            ->where('slug', '=', $slug)
            ->fetchOneAsResource();

        return $resource?->toDomain();
    }
}
