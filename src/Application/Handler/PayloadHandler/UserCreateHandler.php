<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\ConflictException;
use Semitexa\Core\Http\HttpStatus;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserCreatePayload;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResource;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserCreatePayload::class, resource: GenericResponse::class)]
final class UserCreateHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(UserCreatePayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $existing = $this->userRepo->findByEmail($payload->getEmail());
        if ($existing !== null) {
            throw new ConflictException('Email already exists');
        }

        $user = new PlatformUserResource();
        $user->email = $payload->getEmail();
        $user->name = $payload->getName();
        $user->password_hash = password_hash($payload->getPassword(), PASSWORD_DEFAULT);
        $user->is_active = true;

        $this->userRepo->save($user);

        $domain = $user->toDomain();

        $resource->setStatusCode(HttpStatus::Created->value);
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
