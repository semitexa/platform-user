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
use Semitexa\Platform\User\Application\Payload\Request\RoleListPayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleListPayload::class, resource: GenericResponse::class)]
final class RoleListHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        $roles = [];
        foreach ($this->roleRepo->findAll() as $role) {
            $roles[] = [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
                'description' => $role->description,
                'is_system' => $role->isSystem,
            ];
        }

        return Response::json(['roles' => $roles]);
    }
}
