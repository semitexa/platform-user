<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Repository;

use Semitexa\Core\Attributes\SatisfiesRepositoryContract;
use Semitexa\Orm\Repository\AbstractRepository;
use Semitexa\Orm\Uuid\Uuid7;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;
use Semitexa\Platform\User\Domain\Repository\ProfileValueRepositoryInterface;

#[SatisfiesRepositoryContract(of: ProfileValueRepositoryInterface::class)]
class ProfileValueRepository extends AbstractRepository implements ProfileValueRepositoryInterface
{
    protected function getResourceClass(): string
    {
        return ProfileValueResource::class;
    }

    private function normalizeUuid(string $id): string
    {
        if (strlen($id) === 36 && str_contains($id, '-')) {
            return Uuid7::toBytes($id);
        }
        return $id;
    }

    public function findByUserId(string $userId): array
    {
        return $this->select()
            ->where('user_id', '=', $this->normalizeUuid($userId))
            ->fetchAll();
    }

    public function findByUserAndField(string $userId, string $fieldId): ?ProfileValueResource
    {
        return $this->select()
            ->where('user_id', '=', $this->normalizeUuid($userId))
            ->where('field_id', '=', $this->normalizeUuid($fieldId))
            ->fetchOneAsResource();
    }

    public function deleteByFieldId(string $fieldId): void
    {
        $table = $this->getTableName();
        $this->getAdapter()->execute("DELETE FROM `{$table}` WHERE `field_id` = ?", [$this->normalizeUuid($fieldId)]);
    }
}
