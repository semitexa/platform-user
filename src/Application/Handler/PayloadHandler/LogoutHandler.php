<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Auth\Context\AuthManager;
use Semitexa\Auth\Handler\SessionAuthHandler;
use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Session\SessionInterface;
use Semitexa\Platform\User\Application\Payload\Request\LogoutPayload;

#[AsPayloadHandler(payload: LogoutPayload::class, resource: GenericResponse::class)]
final class LogoutHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected SessionInterface $session;

    public function handle(LogoutPayload $payload, GenericResponse $resource): GenericResponse
    {
        $this->session->forget(SessionAuthHandler::SESSION_USER_KEY);
        $this->session->regenerate();

        AuthManager::getInstance()->setUser(null);

        $resource->setContext(['success' => true]);
        return $resource;
    }
}
