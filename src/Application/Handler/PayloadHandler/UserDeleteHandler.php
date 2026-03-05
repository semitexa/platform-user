<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Platform\User\Application\Payload\Request\UserDeletePayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserDeletePayload::class, resource: GenericResponse::class)]
final class UserDeleteHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof UserDeletePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $user = $this->userRepo->findById($payload->id);

        if ($user === null) {
            return Response::json(['error' => 'User not found'], 404);
        }

        $this->userRepo->delete($user);

        return Response::json(['success' => true]);
    }
}
