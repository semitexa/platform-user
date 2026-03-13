<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserRolesSetPayload;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: UserRolesSetPayload::class, resource: GenericResponse::class)]
final class UserRolesSetHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RbacServiceInterface $rbac;

    public function handle(UserRolesSetPayload $payload, GenericResponse $resource): GenericResponse
    {
        // Revoke all existing roles
        $existingRoles = $this->rbac->getUserRoles($payload->id);
        foreach ($existingRoles as $role) {
            $this->rbac->revokeRole($payload->id, $role->id);
        }

        // Assign new roles
        foreach ($payload->getRoleIds() as $roleId) {
            $this->rbac->assignRole($payload->id, $roleId);
        }

        // Return updated roles
        $domainRoles = $this->rbac->getUserRoles($payload->id);

        $roles = [];
        foreach ($domainRoles as $r) {
            $roles[] = [
                'id' => $r->id,
                'slug' => $r->slug,
                'name' => $r->name,
                'description' => $r->description,
                'is_system' => $r->isSystem,
            ];
        }

        $resource->setContext(['roles' => $roles]);
        return $resource;
    }
}
