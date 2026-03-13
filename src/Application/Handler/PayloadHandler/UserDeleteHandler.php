<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserDeletePayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;

#[AsPayloadHandler(payload: UserDeletePayload::class, resource: GenericResponse::class)]
final class UserDeleteHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    public function handle(UserDeletePayload $payload, GenericResponse $resource): GenericResponse
    {
        $user = $this->userRepo->findById($payload->id);

        if ($user === null) {
            throw new NotFoundException('User', $payload->id);
        }

        $this->userRepo->delete($user);

        $resource->setContext(['success' => true]);
        return $resource;
    }
}
