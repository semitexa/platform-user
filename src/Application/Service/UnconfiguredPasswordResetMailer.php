<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\SatisfiesServiceContract;
use Semitexa\Platform\User\Domain\Contract\PasswordResetMailerInterface;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

/**
 * The binding until the project provides its own: a reset link nobody sends
 * is worse than a form that says it cannot send one, so this refuses loudly.
 * PasswordResetService checks for it before it looks the address up, so the
 * refusal is the same for every address and tells nobody which ones exist.
 */
#[SatisfiesServiceContract(of: PasswordResetMailerInterface::class)]
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
