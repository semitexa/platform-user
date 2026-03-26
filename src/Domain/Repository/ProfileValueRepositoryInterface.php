<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;
use Semitexa\Platform\User\Domain\Model\ProfileValue;

interface ProfileValueRepositoryInterface
{
    /** @return list<ProfileValue> */
    public function findByUserId(string $userId): array;

    public function findByUserAndField(string $userId, string $fieldId): ?ProfileValueResource;

    public function save(object $entity): void;

    public function deleteByFieldId(string $fieldId): void;
}
