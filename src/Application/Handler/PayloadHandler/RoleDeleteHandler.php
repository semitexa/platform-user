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
use Semitexa\Platform\User\Application\Payload\Request\RoleDeletePayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleDeletePayload::class, resource: GenericResponse::class)]
final class RoleDeleteHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RoleDeletePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $role = $this->roleRepo->findById($payload->id);

        if ($role === null) {
            return Response::json(['error' => 'Role not found'], 404);
        }

        if ($role->is_system) {
            return Response::json(['error' => 'Cannot delete a system role'], 403);
        }

        $this->roleRepo->delete($role);

        return Response::json(['success' => true]);
    }
}
