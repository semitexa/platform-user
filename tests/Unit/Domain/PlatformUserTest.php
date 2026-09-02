<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Tests\Unit\Domain;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

final class PlatformUserTest extends TestCase
{
    private static function user(
        string $password = 'correct-horse-battery',
        UserStatus $status = UserStatus::Active,
        int $failedAttempts = 0,
        ?\DateTimeImmutable $lockedUntil = null,
    ): PlatformUser {
        return new PlatformUser(
            id: 'u-1',
            email: 'admin@example.test',
            passwordHash: password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]),
            displayName: '',
            tenantId: 'regmus',
            role: UserRole::Admin,
            status: $status,
            failedAttempts: $failedAttempts,
            lockedUntil: $lockedUntil,
        );
    }

    #[Test]
    public function a_correct_password_verifies_and_a_wrong_one_does_not(): void
    {
        $user = self::user();

        self::assertTrue($user->verifyPassword('correct-horse-battery'));
        self::assertFalse($user->verifyPassword('correct-horse-batterY'));
        self::assertFalse($user->verifyPassword(''));
    }

    #[Test]
    public function the_account_locks_only_once_the_threshold_is_reached(): void
    {
        $user = self::user(failedAttempts: 3);

        $fourth = $user->withFailedLogin(maxAttempts: 5, lockSeconds: 900);
        self::assertSame(4, $fourth->getFailedAttempts());
        self::assertFalse($fourth->isLocked());

        $fifth = $fourth->withFailedLogin(maxAttempts: 5, lockSeconds: 900);
        self::assertSame(5, $fifth->getFailedAttempts());
        self::assertTrue($fifth->isLocked());
        self::assertFalse($fifth->canAuthenticate());
    }

    #[Test]
    public function an_expired_lock_no_longer_blocks(): void
    {
        $expired = self::user(lockedUntil: new \DateTimeImmutable('-1 second'));

        self::assertFalse($expired->isLocked());
        self::assertTrue($expired->canAuthenticate());
    }

    #[Test]
    public function unrelated_edits_do_not_quietly_lift_an_active_lock(): void
    {
        // The obvious implementation of a copy-with helper drops any field the
        // caller did not name. For a lock that means renaming someone unlocks
        // their account, which is exactly the kind of silent grant nobody goes
        // looking for.
        $locked = self::user(failedAttempts: 5, lockedUntil: new \DateTimeImmutable('+10 minutes'));

        self::assertTrue($locked->withDisplayName('Renamed')->isLocked());
        self::assertTrue($locked->withRole(UserRole::Editor)->isLocked());
        self::assertTrue($locked->withStatus(UserStatus::Active)->isLocked());
    }

    #[Test]
    public function a_new_password_and_a_successful_sign_in_both_clear_the_lock(): void
    {
        $locked = self::user(failedAttempts: 5, lockedUntil: new \DateTimeImmutable('+10 minutes'));

        $rekeyed = $locked->withPasswordHash(password_hash('a-brand-new-one', PASSWORD_BCRYPT, ['cost' => 4]));
        self::assertFalse($rekeyed->isLocked());
        self::assertSame(0, $rekeyed->getFailedAttempts());
        self::assertTrue($rekeyed->verifyPassword('a-brand-new-one'));

        $signedIn = $locked->withSuccessfulLogin();
        self::assertFalse($signedIn->isLocked());
        self::assertSame(0, $signedIn->getFailedAttempts());
        self::assertNotNull($signedIn->getLastLoginAt());
    }

    #[Test]
    public function a_disabled_account_cannot_authenticate_even_with_the_right_password(): void
    {
        $disabled = self::user(status: UserStatus::Disabled);

        self::assertTrue($disabled->verifyPassword('correct-horse-battery'));
        self::assertFalse($disabled->canAuthenticate());
    }

    #[Test]
    public function the_display_name_falls_back_to_the_address(): void
    {
        self::assertSame('admin@example.test', self::user()->getDisplayName());
    }
}
