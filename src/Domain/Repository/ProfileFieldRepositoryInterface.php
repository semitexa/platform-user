<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Repository;

use Semitexa\Platform\User\Domain\Model\ProfileField;

interface ProfileFieldRepositoryInterface
{
    /** @return list<ProfileField> */
    public function findAll(?int $limit = null): array;

    public function findById(string $id): ?ProfileField;

    public function findBySlug(string $slug): ?ProfileField;

    public function save(object $entity): void;

    public function delete(ProfileField $field): void;
}
