<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Platform\User\Application\Payload\Request\MePagePayload;
use Semitexa\Ssr\Http\Response\HtmlResponse;

#[AsPayloadHandler(payload: MePagePayload::class, resource: HtmlResponse::class)]
final class MePageHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(MePagePayload $payload, HtmlResponse $resource): HtmlResponse
    {
        if ($this->auth->isGuest()) {
            $resource->setRedirect('/platform/login');
            return $resource;
        }

        $resource->renderTemplate('@project-layouts-platform-user/me-profile.html.twig');
        return $resource;
    }
}
