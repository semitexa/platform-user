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
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/users/{id}', methods: ['PATCH'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
class UserUpdatePayload implements PayloadInterface, ValidatablePayload
{
    use EmailValidationTrait;
    use LengthValidationTrait;

    public string $id = '';
    protected ?string $email = null;
    protected ?string $name = null;
    protected ?string $password = null;
    protected ?bool $is_active = null;

    public function setId(string $id): void { $this->id = $id; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): void { $this->email = $email; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): void { $this->password = $password; }

    public function getIsActive(): ?bool { return $this->is_active; }
    public function setIs_active(bool $is_active): void { $this->is_active = $is_active; }

    public function validate(): PayloadValidationResult
    {
        $errors = [];
        if ($this->email !== null) {
            $this->validateEmail('email', $this->email, $errors);
        }
        if ($this->password !== null) {
            $this->validateLength('password', $this->password, 8, null, $errors);
        }
        return new PayloadValidationResult(empty($errors), $errors);
    }
}
