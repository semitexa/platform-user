<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

use Semitexa\Core\Auth\AuthenticatableInterface;

final readonly class User implements AuthenticatableInterface
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public bool $isActive,
    ) {}

    public function getId(): string
    {
        return $this->id;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }
}
