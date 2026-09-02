<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Service;

use Semitexa\Core\Attribute\AsService;
use Semitexa\Core\Attribute\Config;

/**
 * The one place a password becomes a hash.
 *
 * Argon2id when the build offers it, bcrypt otherwise — chosen at runtime rather
 * than pinned, so an install on a PHP without libargon still gets a usable hash
 * instead of a fatal error.
 */
#[AsService]
final class PasswordHasher
{
    /** Minimum accepted length. Short enough not to fight the operator, long enough to matter. */
    public const MIN_LENGTH = 10;

    #[Config(env: 'PLATFORM_USER_PASSWORD_MEMORY_KB', default: 65536)]
    protected int $memoryKb;

    #[Config(env: 'PLATFORM_USER_PASSWORD_TIME_COST', default: 4)]
    protected int $timeCost;

    public function hash(#[\SensitiveParameter] string $rawPassword): string
    {
        $this->assertAcceptable($rawPassword);

        $hash = password_hash($rawPassword, $this->algorithm(), $this->options());
        if ($hash === '') {
            throw new \RuntimeException('Password hashing failed.');
        }

        return $hash;
    }

    /**
     * @throws \InvalidArgumentException when the password is too short or blank
     */
    public function assertAcceptable(#[\SensitiveParameter] string $rawPassword): void
    {
        if (trim($rawPassword) === '') {
            throw new \InvalidArgumentException('Password must not be empty.');
        }

        if (mb_strlen($rawPassword) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException(sprintf(
                'Password must be at least %d characters.',
                self::MIN_LENGTH,
            ));
        }
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, $this->algorithm(), $this->options());
    }

    private function algorithm(): string
    {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
    }

    /** @return array<string, int> */
    private function options(): array
    {
        if (!defined('PASSWORD_ARGON2ID')) {
            return [];
        }

        return [
            'memory_cost' => max(8192, $this->memoryKb ?? 65536),
            'time_cost' => max(2, $this->timeCost ?? 4),
            'threads' => 1,
        ];
    }
}
