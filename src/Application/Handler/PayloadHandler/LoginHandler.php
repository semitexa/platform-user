<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

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
use Semitexa\Platform\User\Application\Payload\Request\LoginPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\UserActivityRepositoryInterface;

#[AsPayloadHandler(payload: LoginPayload::class, resource: GenericResponse::class)]
final class LoginHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected SessionInterface $session;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    #[InjectAsReadonly]
    protected ?UserActivityRepositoryInterface $activityService = null;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof LoginPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $user = $this->userRepo->findByEmail($payload->getEmail());

        if ($user === null || !$user->is_active || !password_verify($payload->getPassword(), $user->password_hash)) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }

        $domain = $user->toDomain();

        $this->session->set(SessionAuthHandler::SESSION_USER_KEY, $domain->getId());
        $this->session->regenerate();

        AuthManager::getInstance()->setUser($domain);

        try {
            $this->activityService?->record(
                $domain->id,
                'login',
            );
        } catch (\Throwable) {
            // Activity recording is best-effort
        }

        return Response::json([
            'user' => [
                'id' => $domain->id,
                'email' => $domain->email,
                'name' => $domain->name,
            ],
        ]);
    }
}
