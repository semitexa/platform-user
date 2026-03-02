<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;

#[AsPayload(path: '/platform/login', methods: ['GET'], responseWith: GenericResponse::class)]
class LoginPagePayload implements PayloadInterface, ValidatablePayload
{
    public function validate(): PayloadValidationResult
    {
        return new PayloadValidationResult(true, []);
    }
}
