<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\ConflictException;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserUpdatePayload::class, resource: GenericResponse::class)]
final class UserUpdateHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(UserUpdatePayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $user = $this->userRepo->findById($payload->id);

        if ($user === null) {
            throw new NotFoundException('User', $payload->id);
        }

        if ($payload->getEmail() !== null) {
            $existing = $this->userRepo->findByEmail($payload->getEmail());
            if ($existing !== null && $existing->id !== $user->id) {
                throw new ConflictException('Email already exists');
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

        $resource->setContext([
            'user' => [
                'id' => $domain->id,
                'email' => $domain->email,
                'name' => $domain->name,
                'is_active' => $domain->isActive,
            ],
        ]);
        return $resource;
    }
}
