<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UnlockPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UnlockPayload::class, resource: GenericResponse::class)]
final class UnlockHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(UnlockPayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $userResource = $this->userRepo->findById($this->auth->getUser()->getId());
        if ($userResource === null || !$userResource->is_active) {
            throw new AuthenticationException();
        }

        if (!password_verify($payload->getPassword(), $userResource->password_hash)) {
            throw new AuthenticationException('Invalid credentials');
        }

        $resource->setContext(['success' => true]);
        return $resource;
    }
}
