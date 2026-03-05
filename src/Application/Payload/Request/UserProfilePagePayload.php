<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Attributes\RequiresPermission;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Ssr\Http\Response\HtmlResponse;

#[AsPayload(path: '/platform/users/{id}', methods: ['GET'], responseWith: HtmlResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[RequiresAuth]
#[RequiresPermission('users.list')]
class UserProfilePagePayload implements PayloadInterface
{
    public string $id = '';

    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
