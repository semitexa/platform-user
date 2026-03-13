<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserRolesGetPayload;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: UserRolesGetPayload::class, resource: GenericResponse::class)]
final class UserRolesGetHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RbacServiceInterface $rbac;

    public function handle(UserRolesGetPayload $payload, GenericResponse $resource): GenericResponse
    {
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
