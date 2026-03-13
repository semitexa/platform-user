<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Attributes\RequiresPermission;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/roles/{id}',
    methods: ['DELETE'],
    requirements: ['id' => '[a-f0-9\\-]{36}'])
]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
#[RequiresAuth]
#[RequiresPermission('roles.manage')]
class RoleDeletePayload
{
    public string $id = '';

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
