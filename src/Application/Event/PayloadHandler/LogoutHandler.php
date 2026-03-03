<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Event\PayloadHandler;

use Semitexa\Auth\Context\AuthManager;
use Semitexa\Auth\Handler\SessionAuthHandler;
use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Core\Session\SessionInterface;
use Semitexa\Platform\User\Application\Payload\Request\LogoutPayload;

#[AsPayloadHandler(payload: LogoutPayload::class, resource: GenericResponse::class)]
final class LogoutHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected SessionInterface $session;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        $this->session->forget(SessionAuthHandler::SESSION_USER_KEY);
        $this->session->regenerate();

        AuthManager::getInstance()->setUser(null);

        return Response::json(['success' => true]);
    }
}
