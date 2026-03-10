<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Validation\Trait\EmailValidationTrait;
use Semitexa\Core\Validation\Trait\NotBlankValidationTrait;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;
use App\Tests\Strategy\LoginEmailFormatStrategy;

// Public endpoint — no #[RequiresAuth]. SecurityStrategy skips automatically.
#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/user/login',
    methods: ['POST'])
]
#[TestablePayload(
    strategies: [
        ParanoiaProfileStrategy::class,    // Standard (Auth, Method, Type) + Monkey + MemoryLeak
        LoginEmailFormatStrategy::class,   // Custom: email format + blank fields → 422
    ]
)]
class LoginPayload implements PayloadInterface, ValidatablePayload
{
    use NotBlankValidationTrait;
    use EmailValidationTrait;

    protected string $email = '';
    protected string $password = '';

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function validate(): PayloadValidationResult
    {
        $errors = [];
        $this->validateNotBlank('email', $this->email, $errors);
        $this->validateEmail('email', $this->email, $errors);
        $this->validateNotBlank('password', $this->password, $errors);
        return new PayloadValidationResult(empty($errors), $errors);
    }
}
