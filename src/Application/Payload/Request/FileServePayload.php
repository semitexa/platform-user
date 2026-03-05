<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Http\Response\GenericResponse;

#[AsPayload(path: '/api/platform/files/{id}', methods: ['GET'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[RequiresAuth]
class FileServePayload implements PayloadInterface
{
    public string $id = '';

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
