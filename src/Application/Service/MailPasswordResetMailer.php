<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\Config;
use Semitexa\Core\Attribute\InjectAsReadonly;
use Semitexa\Core\Attribute\SatisfiesServiceContract;
use Semitexa\Mail\Domain\Contract\MailServiceInterface;
use Semitexa\Mail\Domain\Model\MailEnvelope;
use Semitexa\Mail\Domain\Model\MailRecipient;
use Semitexa\Platform\User\Domain\Contract\PasswordResetMailerInterface;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * The reset link by mail (semitexa/mail), once PLATFORM_USER_RESET_URL says
 * where the link points: a URL with `{token}` in it, such as
 * `https://example.com/reset-password?token={token}`. The site's address comes
 * from there, never from the request's Host header, which the asker controls.
 * Without it, PasswordResetService refuses every request, as before.
 *
 * One address for every site: a multi-site install whose sites live on
 * different hosts binds its own PasswordResetMailerInterface.
 */
#[SatisfiesServiceContract(of: PasswordResetMailerInterface::class)]
final class MailPasswordResetMailer implements PasswordResetMailerInterface
{
    #[InjectAsReadonly]
    protected MailServiceInterface $mail;

    #[Config(env: 'PLATFORM_USER_RESET_URL', default: '')]
    protected string $resetUrl;

    public function isConfigured(): bool
    {
        return self::validTemplate($this->resetUrl ?? '');
    }

    public function sendResetLink(
        PlatformUser $user,
        #[\SensitiveParameter] string $token,
        \DateTimeImmutable $expiresAt,
    ): void {
        if (!$this->isConfigured()) {
            throw UnconfiguredPasswordResetMailer::refusal();
        }
        $link = str_replace('{token}', rawurlencode($token), $this->resetUrl);
        $minutes = max(1, intdiv($expiresAt->getTimestamp() - time() + 59, 60));

        $envelope = new MailEnvelope();
        // The live link: erased from the stored row once the mail is sent.
        $envelope->sensitive = true;
        $envelope->mailKey = 'platform-user.password-reset';
        $envelope->tenantId = $user->getTenantId() !== '' ? $user->getTenantId() : null;
        $envelope->to = [new MailRecipient($user->getEmail(), $user->getDisplayName() !== '' ? $user->getDisplayName() : null)];
        $envelope->subject = 'Reset your password';
        $envelope->textBody = "Someone asked to reset the password of this account.\n\n"
            . "To choose a new one, open this link within {$minutes} minutes:\n{$link}\n\n"
            . "The link works once. If you did not ask for it, ignore this message: your password stays as it is.\n";

        // MailService answers a failure (no mailer config, a queue that is
        // down) with a status, not an exception: thrown here, the service
        // logs it, without the token.
        $result = $this->mail->send($envelope);
        if (!$result->succeeded()) {
            throw new \RuntimeException(sprintf('The reset mail was not sent: %s %s', (string) $result->errorCode, (string) $result->errorMessage));
        }
    }

    /** An absolute http(s) URL with `{token}` in it. */
    public static function validTemplate(string $template): bool
    {
        return str_contains($template, '{token}')
            && preg_match('#^https?://[^/?\#\s]+#i', $template) === 1;
    }
}
