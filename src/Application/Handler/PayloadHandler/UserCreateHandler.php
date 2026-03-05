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
use Semitexa\Platform\User\Application\Payload\Request\UserCreatePayload;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserCreatePayload::class, resource: GenericResponse::class)]
final class UserCreateHandler implements HandlerInterface
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

        if (!$payload instanceof UserCreatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $existing = $this->userRepo->findByEmail($payload->getEmail());
        if ($existing !== null) {
            return Response::json(['error' => 'Email already exists'], 409);
        }

        $user = new PlatformUserResource();
        $user->email = $payload->getEmail();
        $user->name = $payload->getName();
        $user->password_hash = password_hash($payload->getPassword(), PASSWORD_DEFAULT);
        $user->is_active = true;

        $this->userRepo->save($user);

        $domain = $user->toDomain();

        return Response::json([
            'user' => [
                'id' => $domain->id,
                'email' => $domain->email,
                'name' => $domain->name,
                'is_active' => $domain->isActive,
            ],
        ], 201);
    }
}
