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
use Semitexa\Platform\User\Application\Payload\Request\UserRolesGetPayload;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: UserRolesGetPayload::class, resource: GenericResponse::class)]
final class UserRolesGetHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected RbacServiceInterface $rbac;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof UserRolesGetPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

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

        return Response::json(['roles' => $roles]);
    }
}
