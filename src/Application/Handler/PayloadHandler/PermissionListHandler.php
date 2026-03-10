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
use Semitexa\Platform\User\Application\Payload\Request\PermissionListPayload;
use Semitexa\Platform\User\Domain\Repository\PermissionRepositoryInterface;

#[AsPayloadHandler(payload: PermissionListPayload::class, resource: GenericResponse::class)]
final class PermissionListHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected PermissionRepositoryInterface $permRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
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

        return Response::json(['permissions' => $permissions]);
    }
}
