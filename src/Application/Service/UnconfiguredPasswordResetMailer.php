<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Platform\User\Domain\Contract\PasswordResetMailerInterface;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * A mailer that refuses: a reset link nobody sends is worse than a form that
 * says it cannot send one. No longer the default binding — that is
 * MailPasswordResetMailer, which refuses the same way until
 * PLATFORM_USER_RESET_URL is set; its refusal() is shared. PasswordResetService checks before it looks the address
 * up, so the refusal is the same for every address and tells nobody which
 * ones exist.
 */
final class UnconfiguredPasswordResetMailer implements PasswordResetMailerInterface
{
    public function sendResetLink(
        PlatformUser $user,
        #[\SensitiveParameter] string $token,
        \DateTimeImmutable $expiresAt,
    ): void {
        throw self::refusal();
    }

    public static function refusal(): \LogicException
    {
        return new \LogicException(
            'Password reset has no mailer: bind ' . PasswordResetMailerInterface::class
            . ' in a module (#[SatisfiesServiceContract]) to send the reset link.',
        );
    }
}
