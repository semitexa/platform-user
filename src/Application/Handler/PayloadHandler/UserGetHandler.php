<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserGetPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserGetPayload::class, resource: GenericResponse::class)]
final class UserGetHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(UserGetPayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $result = $this->userRepo->findById($payload->id);

        if ($result === null) {
            throw new NotFoundException('User', $payload->id);
        }

        $domain = $result->toDomain();

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
