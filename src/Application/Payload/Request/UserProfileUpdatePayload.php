<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/users/{id}/profile', methods: ['PATCH'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
class UserProfileUpdatePayload
{
    public string $id = '';
    protected array $fields = [];

    public function setId(string $id): void { $this->id = $id; }

    public function getFields(): array { return $this->fields; }
    public function setFields(array $fields): void { $this->fields = $fields; }
}
