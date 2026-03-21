<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/users/me/profile',
    methods: ['PATCH']
)]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
class MeProfileUpdatePayload
{
    protected array $fields = [];

    public function getFields(): array { return $this->fields; }
    public function setFields(array $fields): void { $this->fields = $fields; }
}
