<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Validation\Trait\NotBlankValidationTrait;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/user/unlock',
    methods: ['POST']
)]
final class UnlockPayload implements ValidatablePayload
{
    use NotBlankValidationTrait;

    protected string $password = '';

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
        $this->validateNotBlank('password', $this->password, $errors);
        return new PayloadValidationResult(empty($errors), $errors);
    }
}
