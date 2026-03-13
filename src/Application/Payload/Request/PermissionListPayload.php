<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/permissions',
    methods: ['GET']
)]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
#[RequiresAuth]
class PermissionListPayload
{
}
