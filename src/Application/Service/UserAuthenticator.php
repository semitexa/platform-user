<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\Config;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Contract\FailedLoginCounterInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\AuthFailure;
use Semitexa\Platform\User\Domain\Model\AuthAttempt;

/**
 * Verifies one email/password pair and keeps the account's failure counters.
 *
 * Everything a login route needs to get right lives here rather than in the
 * route: the lockout window, the constant-time comparison, the opportunistic
 * re-hash when the cost parameters have moved on, and the dummy verification
 * that keeps a missing account from answering faster than a wrong password.
 */
#[AsService]
final class UserAuthenticator
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    #[InjectAsReadonly]
    protected PasswordHasher $hasher;

    /** Failures before the account stops accepting attempts. */
    #[Config(env: 'PLATFORM_USER_MAX_ATTEMPTS', default: 5)]
    protected int $maxAttempts;

    /** A hash with the current cost, verified against when there is no real one to check. */
    private ?string $timingHash = null;

    /** How long the lockout lasts, in seconds. */
    #[Config(env: 'PLATFORM_USER_LOCK_SECONDS', default: 900)]
    protected int $lockSeconds;

    public function attempt(
        string $email,
        #[\SensitiveParameter] string $password,
        ?string $tenantId = null,
    ): AuthAttempt {
        $user = $this->users->findByEmail(PlatformUserRepository::normalizeEmail($email), $tenantId);

        if ($user === null) {
            // Hash something anyway. Without this, "no such account" returns in
            // microseconds while a real account spends the full Argon2 cost, and
            // the difference is measurable enough to enumerate addresses.
            $this->burnTime($password);

            return AuthAttempt::denied(AuthFailure::NoSuchUser);
        }

        if (!$user->getStatus()->canAuthenticate()) {
            $this->burnTime($password);

            return AuthAttempt::denied(AuthFailure::Disabled);
        }

        if ($user->isLocked()) {
            $this->burnTime($password);

            return AuthAttempt::denied(AuthFailure::Locked, $user->getLockedUntil());
        }

        // The attempt is taken before the password is checked: once the
        // account has used up its attempts, nothing more is checked, however
        // many guesses arrive at once.
        if ($this->users instanceof FailedLoginCounterInterface) {
            try {
                $reserved = $this->users->reserveLoginAttempt($user, $this->maxAttempts ?? 5);
            } catch (\Throwable) {
                $reserved = true; // see persist(): bookkeeping never decides alone
            }
            if (!$reserved) {
                // Attempts used up but no lock written (its write failed, or
                // the request was cancelled in between) left the account
                // refused with no end: written now, it ends like any lockout.
                try {
                    $this->users->lockIfExhausted($user, $this->maxAttempts ?? 5, $this->lockSeconds ?? 900);
                } catch (\Throwable) {
                }
                $this->burnTime($password);

                return AuthAttempt::denied(AuthFailure::Locked);
            }
        }

        if (!$user->verifyPassword($password)) {
            $failed = $this->recordFailure($user);

            return $failed->isLocked()
                ? AuthAttempt::denied(AuthFailure::Locked, $failed->getLockedUntil())
                : AuthAttempt::denied(AuthFailure::WrongPassword);
        }

        $signedIn = $user->withSuccessfulLogin();

        // The cost parameters may have been raised since this hash was made;
        // a correct password is the only moment we hold the plaintext needed
        // to upgrade it.
        if ($this->hasher->needsRehash($signedIn->getPasswordHash())) {
            $signedIn = $signedIn->withRehashedPassword($this->hasher->hash($password));
        }

        $this->persist($signedIn);

        return AuthAttempt::granted($signedIn);
    }

    /**
     * Bookkeeping must never be what stops someone signing in, nor what tells an
     * attacker that an address exists — swallow the write error and carry on.
     */
    private function persist(\Semitexa\Platform\User\Domain\Model\PlatformUser $user): void
    {
        try {
            $this->users->update($user);
        } catch (\Throwable) {
            // Intentionally ignored: see the docblock.
        }
    }

    /** The failure as counted by the database where it can be; from the row in memory otherwise. */
    private function recordFailure(\Semitexa\Platform\User\Domain\Model\PlatformUser $user): \Semitexa\Platform\User\Domain\Model\PlatformUser
    {
        $max = $this->maxAttempts ?? 5;
        $seconds = $this->lockSeconds ?? 900;
        if ($this->users instanceof FailedLoginCounterInterface) {
            try {
                // Already counted when the attempt was taken.
                return $this->users->lockIfExhausted($user, $max, $seconds) ?? $user;
            } catch (\Throwable) {
                return $user; // see persist()
            }
        }
        $failed = $user->withFailedLogin($max, $seconds);
        $this->persist($failed);

        return $failed;
    }

    /**
     * Spend what checking a real password spends, whatever was typed: hashing
     * the input threw at once for a password too short to be accepted, so an
     * unknown address answered in microseconds and a known one in a tenth of
     * a second — enough to tell which addresses have accounts.
     */
    private function burnTime(#[\SensitiveParameter] string $password): void
    {
        try {
            // Made once, with the cost real hashes have, then only verified.
            $this->timingHash ??= $this->hasher->hash('timing-equalizer-not-a-password');
        } catch (\Throwable) {
            return;
        }
        password_verify($password, $this->timingHash);
    }
}
