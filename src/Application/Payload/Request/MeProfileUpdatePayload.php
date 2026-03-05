<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Http\Response\GenericResponse;

#[AsPayload(path: '/api/platform/users/me/profile', methods: ['PATCH'], responseWith: GenericResponse::class)]
#[RequiresAuth]
class MeProfileUpdatePayload implements PayloadInterface
{
    protected array $fields = [];

    public function getFields(): array { return $this->fields; }
    public function setFields(array $fields): void { $this->fields = $fields; }
}
