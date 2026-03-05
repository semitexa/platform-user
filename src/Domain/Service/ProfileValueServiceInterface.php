<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;

interface ProfileValueServiceInterface
{
    /** @return list<ProfileValueResource> */
    public function findByUserId(string $userId): array;

    public function findByUserAndField(string $userId, string $fieldId): ?ProfileValueResource;

    public function save(ProfileValueResource $resource): void;

    public function deleteByFieldId(string $fieldId): void;
}
