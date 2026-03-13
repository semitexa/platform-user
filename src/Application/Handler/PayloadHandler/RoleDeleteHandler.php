<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AccessDeniedException;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\RoleDeletePayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleDeletePayload::class, resource: GenericResponse::class)]
final class RoleDeleteHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(RoleDeletePayload $payload, GenericResponse $resource): GenericResponse
    {
        $role = $this->roleRepo->findById($payload->id);

        if ($role === null) {
            throw new NotFoundException('Role', $payload->id);
        }

        if ($role->is_system) {
            throw new AccessDeniedException('Cannot delete a system role');
        }

        $this->roleRepo->delete($role);

        $resource->setContext(['success' => true]);
        return $resource;
    }
}
