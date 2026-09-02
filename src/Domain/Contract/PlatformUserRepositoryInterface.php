<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Contract;

use Semitexa\Platform\User\Domain\Model\PlatformUser;

interface PlatformUserRepositoryInterface
{
    public function findById(string $id): ?PlatformUser;

    /**
     * Resolve one sign-in candidate.
     *
     * A null tenant means "any tenant": the caller has no site context yet and
     * accepts a match only when it is unambiguous — an email registered against
     * two tenants returns null rather than guessing which site to let them into.
     */
    public function findByEmail(string $email, ?string $tenantId = null): ?PlatformUser;

    /** @return list<PlatformUser> */
    public function findAll(?string $tenantId = null, bool $includeDisabled = false): array;

    public function save(PlatformUser $user): void;

    public function update(PlatformUser $user): void;

    public function delete(string $id): void;
}
