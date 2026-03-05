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
use Semitexa\Platform\User\Application\Payload\Request\UserUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserUpdatePayload::class, resource: GenericResponse::class)]
final class UserUpdateHandler implements HandlerInterface
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

        if (!$payload instanceof UserUpdatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $user = $this->userRepo->findById($payload->id);

        if ($user === null) {
            return Response::json(['error' => 'User not found'], 404);
        }

        if ($payload->getEmail() !== null) {
            $existing = $this->userRepo->findByEmail($payload->getEmail());
            if ($existing !== null && $existing->id !== $user->id) {
                return Response::json(['error' => 'Email already exists'], 409);
            }
            $user->email = $payload->getEmail();
        }

        if ($payload->getName() !== null) {
            $user->name = $payload->getName();
        }

        if ($payload->getPassword() !== null) {
            $user->password_hash = password_hash($payload->getPassword(), PASSWORD_DEFAULT);
        }

        if ($payload->getIsActive() !== null) {
            $user->is_active = $payload->getIsActive();
        }

        $this->userRepo->save($user);

        $domain = $user->toDomain();

        return Response::json([
            'user' => [
                'id' => $domain->id,
                'email' => $domain->email,
                'name' => $domain->name,
                'is_active' => $domain->isActive,
            ],
        ]);
    }
}
