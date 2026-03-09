<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileFieldResource;
use Semitexa\Platform\User\Domain\Model\ProfileField;

interface ProfileFieldRepositoryInterface
{
    /** @return list<ProfileField> */
    public function findAll(?int $limit = null): array;

    public function findById(string $id): ?ProfileFieldResource;

    public function findBySlug(string $slug): ?ProfileFieldResource;

    public function save(ProfileFieldResource $resource): void;

    public function delete(ProfileFieldResource $resource): void;
}
