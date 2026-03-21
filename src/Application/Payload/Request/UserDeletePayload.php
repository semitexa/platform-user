<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;
use App\Tests\Auth\SessionTestTokenProvider;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/users/{id}',
    methods: ['DELETE'],
    requirements: ['id' => '[a-f0-9\\-]{36}'])
]
#[TestablePayload(
    strategies: [StandardProfileStrategy::class],
    context: [
        // Session-based auth: token = full Cookie header value, scheme = '' (no prefix)
        'auth_header'  => 'Cookie',
        'auth_scheme'  => '',
        'token_provider' => SessionTestTokenProvider::class,
        // Do not require a real seeded session for contract tests in release preflight.
        'security_skip_valid_token_check' => true,
        // id is a URL path parameter — type cannot be mutated via request body
        'type_mutation' => false,
    ]
)]
class UserDeletePayload
{
    public string $id = '';

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
