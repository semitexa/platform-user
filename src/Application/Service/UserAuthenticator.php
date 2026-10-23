<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\Config;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Tenant\TenantContextAccess;
use Semitexa\Core\Tenant\TenantContextInterface;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Contract\FailedLoginCounterInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserEmailLookupInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Enum\AuthFailure;
use Semitexa\Platform\User\Domain\Model\AuthAttempt;
use Semitexa\Platform\User\Domain\Model\PlatformUser;
use Semitexa\Platform\User\Domain\Service\SiteAccess;

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
        return $this->check($this->users->findByEmail(PlatformUserRepository::normalizeEmail($email), $tenantId), $password);
    }

    /**
     * attempt() for a request to one site: only the accounts that may act on
     * that site are candidates (SiteAccess), before any password is checked.
     * Checked first and refused after, a correct password for another site's
     * account answered apart from a wrong one (its own message, a slower
     * re-hash), was stamped as a sign-in, and reset that account's failures;
     * and guesses from one site locked other sites' accounts out. An email
     * registered with two sites signs in at each, where it was refused at
     * both. A null $site (no tenancy) allows every account, as SiteAccess does.
     */
    public function attemptAt(
        string $email,
        #[\SensitiveParameter] string $password,
        ?TenantContextInterface $site,
    ): AuthAttempt {
        $email = PlatformUserRepository::normalizeEmail($email);
        $candidates = $this->users instanceof PlatformUserEmailLookupInterface
            ? $this->users->findAllByEmail($email)
            : self::fallbackCandidates($this->users, $email, $site);
        $allowed = array_values(array_filter(
            $candidates,
            static fn (PlatformUser $user): bool => SiteAccess::allows($user, $site),
        ));
        // Two accounts this site accepts (an owner and a site user sharing
        // the address) have no single right answer, as in findByEmail();
        // unless only one of them can sign in at all: a disabled row then
        // shut the active one out of its own site.
        if (count($allowed) > 1) {
            $allowed = array_values(array_filter($allowed, static fn (PlatformUser $user): bool => $user->getStatus()->canAuthenticate()));
        }

        return $this->check(count($allowed) === 1 ? $allowed[0] : null, $password);
    }

    /**
     * A repository without findAllByEmail(): this site's account first, then
     * the address on its own, which finds an owner when it names one account.
     *
     * @return list<PlatformUser>
     */
    private static function fallbackCandidates(PlatformUserRepositoryInterface $users, string $email, ?TenantContextInterface $site): array
    {
        $tenantId = TenantContextAccess::tenantId($site);
        $user = ($tenantId !== null ? $users->findByEmail($email, $tenantId) : null) ?? $users->findByEmail($email);

        return $user === null ? [] : [$user];
    }

    private function check(?PlatformUser $user, #[\SensitiveParameter] string $password): AuthAttempt
    {
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
