<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Service;

use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileFieldResource;

interface ProfileFieldServiceInterface
{
    /** @return list<ProfileFieldResource> */
    public function findAll(): array;

    public function findById(string $id): ?ProfileFieldResource;

    public function findBySlug(string $slug): ?ProfileFieldResource;

    public function save(ProfileFieldResource $resource): void;

    public function delete(ProfileFieldResource $resource): void;
}
