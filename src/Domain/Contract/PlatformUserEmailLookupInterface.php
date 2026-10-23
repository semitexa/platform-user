<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Contract;

use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * Every account an email is registered with, across sites. Apart from
 * PlatformUserRepositoryInterface so a project's own repository does not
 * break; without it, a sign-in finds an account only when the email names
 * one, as findByEmail() does.
 */
interface PlatformUserEmailLookupInterface
{
    /** @return list<PlatformUser> oldest first */
    public function findAllByEmail(string $email): array;
}
