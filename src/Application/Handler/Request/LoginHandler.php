<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\Request;

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
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Payload\Request\LoginPayload;
use Semitexa\Platform\User\Application\Resource\PlatformUserRepository;

#[AsPayloadHandler(payload: LoginPayload::class, resource: GenericResponse::class)]
final class LoginHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected SessionInterface $session;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof LoginPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $email = $payload->getEmail();
        $password = $payload->getPassword();

        $result = OrmManager::run(function (OrmManager $orm) use ($email, $password) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            $user = $repo->findByEmail($email);

            if ($user === null) {
                return null;
            }

            if (!$user->is_active) {
                return null;
            }

            if (!password_verify($password, $user->password_hash)) {
                return null;
            }

            return $user->toDomain();
        });

        if ($result === null) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }

        $this->session->set(SessionAuthHandler::SESSION_USER_KEY, $result->getId());
        $this->session->regenerate();

        AuthManager::getInstance()->setUser($result);

        return Response::json([
            'user' => [
                'id' => $result->id,
                'email' => $result->email,
                'name' => $result->name,
            ],
        ]);
    }
}
