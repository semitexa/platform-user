<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;

#[AsPayload(path: '/api/platform/roles', methods: ['GET'], responseWith: GenericResponse::class)]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
class RoleListPayload
{
}
