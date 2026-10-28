<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Contract;

use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * Delivers a password-reset link to the account's owner.
 *
 * MailPasswordResetMailer is the default (semitexa/mail, PLATFORM_USER_RESET_URL);
 * a project binds its own (#[SatisfiesServiceContract]) for other wording, or
 * sites on different hosts: it knows the site's address, the page that serves
 * the reset form, and how it sends mail. The
 * link it builds carries $token as given; the token is a secret that resets
 * the password until $expiresAt, so it belongs in the mail and nowhere else
 * (not in a log line, not in an analytics event).
 *
 * - Build the link from the site's configured address, never from the
 *   request's Host header: a forged Host would mail the owner a link to
 *   someone else's domain.
 * - Repeat #[\SensitiveParameter] on $token in the implementation: PHP takes
 *   it from the implementing method, not from this interface.
 * - Mail kept after sending (a queued mail stores its rendered body) holds
 *   the live link until it expires; keep the TTL short if that store is read
 *   by more people than the account owner.
 */
interface PasswordResetMailerInterface
{
    public function sendResetLink(
        PlatformUser $user,
        #[\SensitiveParameter] string $token,
        \DateTimeImmutable $expiresAt,
    ): void;
}
