<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/files/{id}',
    methods: ['GET'],
    requirements: ['id' => '[a-f0-9\\-]{36}']
)]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
class FileServePayload
{
    public string $id = '';

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
