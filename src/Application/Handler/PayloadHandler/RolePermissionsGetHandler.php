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
use Semitexa\Platform\User\Application\Payload\Request\RolePermissionsGetPayload;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: RolePermissionsGetPayload::class, resource: GenericResponse::class)]
final class RolePermissionsGetHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected RbacServiceInterface $rbac;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RolePermissionsGetPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

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

        return Response::json(['permissions' => $permissions]);
    }
}
