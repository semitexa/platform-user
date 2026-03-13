<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\RolePermissionsGetPayload;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: RolePermissionsGetPayload::class, resource: GenericResponse::class)]
final class RolePermissionsGetHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RbacServiceInterface $rbac;

    public function handle(RolePermissionsGetPayload $payload, GenericResponse $resource): GenericResponse
    {
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
