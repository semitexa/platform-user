<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\Config;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Log\LoggerInterface;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;
use Semitexa\Platform\User\Domain\Contract\PasswordResetMailerInterface;
use Semitexa\Platform\User\Domain\Contract\PlatformUserRepositoryInterface;
use Semitexa\Platform\User\Domain\Model\PasswordResetResult;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * "Forgot password" behind the platform.forgot-password and
 * platform.reset-password components: a single-use link that expires, sent by
 * the PasswordResetMailerInterface bound (MailPasswordResetMailer by default).
 *
 * request() answers nothing: an unknown address, a disabled account and an
 * account over its request limit look exactly like a sent link, or the form
 * becomes a way to find out which addresses have accounts. reset() checks the
 * new password before it uses the link up, so a refused password leaves the
 * link working for the next try.
 */
#[AsService]
final class PasswordResetService
{
    #[InjectAsReadonly]
    protected PlatformUserRepositoryInterface $users;

    #[InjectAsReadonly]
    protected PasswordHasher $hasher;

    #[InjectAsReadonly]
    protected PasswordResetTokenStore $tokens;

    #[InjectAsReadonly]
    protected PasswordResetMailerInterface $mailer;

    #[InjectAsReadonly]
    protected LoggerInterface $logger;

    /** How long a reset link works, in seconds. */
    #[Config(env: 'PLATFORM_USER_RESET_TTL_SECONDS', default: 3600)]
    protected int $ttlSeconds;

    /** Links one account may be sent within the window below. */
    #[Config(env: 'PLATFORM_USER_RESET_MAX_REQUESTS', default: 3)]
    protected int $maxRequests;

    /** The request-limit window, in seconds. */
    #[Config(env: 'PLATFORM_USER_RESET_WINDOW_SECONDS', default: 900)]
    protected int $windowSeconds;

    public function request(string $email, ?string $tenantId = null, ?\DateTimeImmutable $now = null): void
    {
        if ($this->mailer instanceof UnconfiguredPasswordResetMailer
            || ($this->mailer instanceof MailPasswordResetMailer && !$this->mailer->isConfigured())) {
            throw UnconfiguredPasswordResetMailer::refusal();
        }

        $now ??= new \DateTimeImmutable();
        $user = $this->users->findByEmail(PlatformUserRepository::normalizeEmail($email), $tenantId);
        if ($user === null || !$user->getStatus()->canAuthenticate()) {
            return;
        }

        // Only a real account gets this far, so a failure here must not reach
        // the caller: a mail server that is down would answer 500 for real
        // addresses and success for unknown ones. Logged, without the token.
        try {
            $this->issueAndSend($user, $now);
        } catch (\Throwable $e) {
            $this->logger->error('Password reset link was not sent.', [
                'user_id' => $user->getId(),
                'error' => $e::class . ': ' . $e->getMessage(),
            ]);
        }
    }

    private function issueAndSend(PlatformUser $user, \DateTimeImmutable $now): void
    {
        $window = max(1, $this->windowSeconds ?? 900);
        $ttl = max(60, $this->ttlSeconds ?? 3600);
        // Links that expired before the window began no longer count towards
        // the limit and can open nothing: nothing needs them any more.
        $this->tokens->purgeExpiredBefore($now->modify("-{$window} seconds"));

        // Count, then issue: two requests in the same instant can both pass.
        // The limit bounds mail to one inbox, it is not a lock; per-client
        // throttling belongs in front of the form.
        if ($this->tokens->countIssuedSince($user->getId(), $now->modify("-{$window} seconds")) >= max(1, $this->maxRequests ?? 3)) {
            return;
        }

        $expiresAt = $now->modify("+{$ttl} seconds");
        $token = $this->tokens->issue($user->getId(), $user->getTenantId(), $now, $expiresAt, $user->getCredentialVersion());
        $this->mailer->sendResetLink($user, $token, $expiresAt);
    }

    public function reset(
        #[\SensitiveParameter] string $token,
        #[\SensitiveParameter] string $newPassword,
        ?\DateTimeImmutable $now = null,
    ): PasswordResetResult {
        try {
            $this->hasher->assertAcceptable($newPassword);
        } catch (\InvalidArgumentException $e) {
            return PasswordResetResult::passwordRejected($e->getMessage());
        }

        $now ??= new \DateTimeImmutable();
        $claimed = $this->tokens->claim($token, $now);
        if ($claimed === null) {
            return PasswordResetResult::invalidToken();
        }

        $user = $this->users->findById($claimed['user_id']);
        if ($user === null || !$user->getStatus()->canAuthenticate()) {
            return PasswordResetResult::invalidToken();
        }
        // A password set since the link was sent — changed in the console, by
        // user:password, or by another link — voids it: only the reset flow
        // revoked links, so an attacker's live link outlived a change meant
        // to lock them out.
        if ($claimed['credential_version'] !== $user->getCredentialVersion()) {
            return PasswordResetResult::invalidToken();
        }

        // withPasswordHash() also clears the lockout and the operator-issued
        // flag: the owner proved they hold the mailbox and chose this password.
        $this->users->update($user->withPasswordHash($this->hasher->hash($newPassword)));
        // Any other link sent before this reset would change the password back
        // to whatever its holder picks.
        $this->tokens->revokeAll($user->getId(), $now);

        return PasswordResetResult::reset();
    }
}
