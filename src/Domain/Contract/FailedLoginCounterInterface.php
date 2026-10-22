<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Contract;

use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * Counting sign-in attempts in the database, not from the row as read.
 * Apart from PlatformUserRepositoryInterface so a project's own repository
 * does not break; without it, the count is written from the row in memory.
 */
interface FailedLoginCounterInterface
{
    /**
     * Take one attempt BEFORE the password is checked: false once the account
     * has used up $maxAttempts or is locked, and then nothing is checked. A
     * lockout that ran out starts the count from zero. Counted after the
     * check, a burst of guesses sent at once was checked in full before the
     * first failure was written.
     */
    public function reserveLoginAttempt(PlatformUser $user, int $maxAttempts): bool;

    /** After a failed check: lock the account once its attempts reach $maxAttempts. Returns it as stored, or null when it is gone. */
    public function lockIfExhausted(PlatformUser $user, int $maxAttempts, int $lockSeconds): ?PlatformUser;
}
