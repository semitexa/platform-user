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
use Semitexa\Platform\User\Application\Payload\Request\RoleUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleUpdatePayload::class, resource: GenericResponse::class)]
final class RoleUpdateHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RoleUpdatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $role = $this->roleRepo->findById($payload->id);

        if ($role === null) {
            return Response::json(['error' => 'Role not found'], 404);
        }

        if ($payload->getName() !== null) {
            $role->name = $payload->getName();
        }

        if ($payload->getDescription() !== null) {
            $role->description = $payload->getDescription();
        }

        $this->roleRepo->save($role);

        $domain = $role->toDomain();

        return Response::json([
            'role' => [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'name' => $domain->name,
                'description' => $domain->description,
                'is_system' => $domain->isSystem,
            ],
        ]);
    }
}
