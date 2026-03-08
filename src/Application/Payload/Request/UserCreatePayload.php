<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Validation\Trait\EmailValidationTrait;
use Semitexa\Core\Validation\Trait\LengthValidationTrait;
use Semitexa\Core\Validation\Trait\NotBlankValidationTrait;
use Semitexa\Testing\Attributes\TestablePayload;
use App\Tests\Strategy\UserCreatePasswordLengthStrategy;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/users',
    methods: ['POST'])
]
#[TestablePayload(
    strategies: [
        ParanoiaProfileStrategy::class,      // Standard (Auth, Method, Type) + Monkey + MemoryLeak
        UserCreatePasswordLengthStrategy::class, // Custom: password < 8 chars → 422
    ]
)]
class UserCreatePayload implements PayloadInterface, ValidatablePayload
{
    use NotBlankValidationTrait;
    use EmailValidationTrait;
    use LengthValidationTrait;

    protected string $email = '';
    protected string $name = '';
    protected string $password = '';

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }

    public function validate(): PayloadValidationResult
    {
        $errors = [];
        $this->validateNotBlank('email', $this->email, $errors);
        $this->validateEmail('email', $this->email, $errors);
        $this->validateNotBlank('name', $this->name, $errors);
        $this->validateNotBlank('password', $this->password, $errors);
        $this->validateLength('password', $this->password, 8, null, $errors);
        return new PayloadValidationResult(empty($errors), $errors);
    }
}
