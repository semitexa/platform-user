<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Response;
use Semitexa\Platform\User\Application\Payload\Request\MePagePayload;
use Semitexa\Ssr\Http\Response\HtmlResponse;

#[AsPayloadHandler(payload: MePagePayload::class, resource: HtmlResponse::class)]
final class MePageHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::redirect('/platform/login');
        }

        if (!$resource instanceof HtmlResponse) {
            return $resource;
        }

        $resource->renderTemplate('@project-layouts-platform-user/me-profile.html.twig');
        return $resource;
    }
}
