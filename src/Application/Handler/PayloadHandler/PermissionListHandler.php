<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\PermissionListPayload;
use Semitexa\Platform\User\Domain\Repository\PermissionRepositoryInterface;

#[AsPayloadHandler(payload: PermissionListPayload::class, resource: GenericResponse::class)]
final class PermissionListHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected PermissionRepositoryInterface $permRepo;

    public function handle(PermissionListPayload $payload, GenericResponse $resource): GenericResponse
    {
        $permissions = [];
        foreach ($this->permRepo->findAll() as $perm) {
            $permissions[] = [
                'id' => $perm->id,
                'slug' => $perm->slug,
                'name' => $perm->name,
                'group_key' => $perm->groupKey,
            ];
        }

        $resource->setContext(['permissions' => $permissions]);
        return $resource;
    }
}
