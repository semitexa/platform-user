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
use Semitexa\Platform\User\Application\Payload\Request\UserViewPagePayload;
use Semitexa\Ssr\Http\Response\HtmlResponse;

#[AsPayloadHandler(payload: UserViewPagePayload::class, resource: HtmlResponse::class)]
final class UserViewPageHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::redirect('/platform/login');
        }

        if (!$payload instanceof UserViewPagePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        if (!$resource instanceof HtmlResponse) {
            return $resource;
        }

        $resource->renderTemplate('@project-layouts-platform-user/user-profile.html.twig', [
            'userId' => $payload->id,
        ]);
        return $resource;
    }
}
