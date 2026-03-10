<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Platform\User\Application\Payload\Request\UnlockPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UnlockPayload::class, resource: GenericResponse::class)]
final class UnlockHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UnlockPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $userResource = $this->userRepo->findById($this->auth->getUser()->getId());
        if ($userResource === null || !$userResource->is_active) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!password_verify($payload->getPassword(), $userResource->password_hash)) {
            return Response::json(['error' => 'Invalid credentials'], 401);
        }

        return Response::json(['success' => true]);
    }
}
