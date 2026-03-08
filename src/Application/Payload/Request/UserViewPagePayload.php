<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Ssr\Http\Response\HtmlResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;

#[AsPayload(path: '/platform/users/view', methods: ['GET'], responseWith: HtmlResponse::class)]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
#[RequiresAuth]
class UserViewPagePayload implements PayloadInterface
{
    public string $id = '';

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
