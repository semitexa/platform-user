<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;
use Semitexa\Authorization\Attributes\PublicEndpoint;

#[PublicEndpoint]
#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/platform/login',
    methods: ['GET']
)]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
class LoginPagePayload implements ValidatablePayload
{
    public function validate(): PayloadValidationResult
    {
        return new PayloadValidationResult(true, []);
    }
}
