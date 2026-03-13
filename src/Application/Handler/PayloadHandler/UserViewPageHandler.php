<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Platform\User\Application\Payload\Request\UserViewPagePayload;
use Semitexa\Ssr\Http\Response\HtmlResponse;

#[AsPayloadHandler(payload: UserViewPagePayload::class, resource: HtmlResponse::class)]
final class UserViewPageHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(UserViewPagePayload $payload, HtmlResponse $resource): HtmlResponse
    {
        if ($this->auth->isGuest()) {
            $resource->setRedirect('/platform/login');
            return $resource;
        }

        $resource->renderTemplate('@project-layouts-platform-user/user-profile.html.twig', [
            'userId' => $payload->id,
        ]);
        return $resource;
    }
}
