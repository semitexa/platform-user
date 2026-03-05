<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Ssr\Http\Response\HtmlResponse;

#[AsPayload(path: '/platform/users/me', methods: ['GET'], responseWith: HtmlResponse::class)]
#[RequiresAuth]
class MePagePayload implements PayloadInterface
{
}
