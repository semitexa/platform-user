<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\RolePermissionsSetPayload;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: RolePermissionsSetPayload::class, resource: GenericResponse::class)]
final class RolePermissionsSetHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RbacServiceInterface $rbac;

    public function handle(RolePermissionsSetPayload $payload, GenericResponse $resource): GenericResponse
    {
        $this->rbac->setRolePermissions($payload->id, $payload->getPermissionIds());

        $domainPermissions = $this->rbac->getRolePermissions($payload->id);

        $permissions = [];
        foreach ($domainPermissions as $p) {
            $permissions[] = [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'group_key' => $p->groupKey,
            ];
        }

        $resource->setContext(['permissions' => $permissions]);
        return $resource;
    }
}
