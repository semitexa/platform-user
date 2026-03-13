<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\RoleUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleUpdatePayload::class, resource: GenericResponse::class)]
final class RoleUpdateHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(RoleUpdatePayload $payload, GenericResponse $resource): GenericResponse
    {
        $role = $this->roleRepo->findById($payload->id);

        if ($role === null) {
            throw new NotFoundException('Role', $payload->id);
        }

        if ($payload->getName() !== null) {
            $role->name = $payload->getName();
        }

        if ($payload->getDescription() !== null) {
            $role->description = $payload->getDescription();
        }

        $this->roleRepo->save($role);

        $domain = $role->toDomain();

        $resource->setContext([
            'role' => [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'name' => $domain->name,
                'description' => $domain->description,
                'is_system' => $domain->isSystem,
            ],
        ]);
        return $resource;
    }
}
