<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\ConflictException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RoleResource;
use Semitexa\Platform\User\Application\Payload\Request\RoleCreatePayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleCreatePayload::class, resource: GenericResponse::class)]
final class RoleCreateHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(RoleCreatePayload $payload, GenericResponse $resource): GenericResponse
    {
        $existing = $this->roleRepo->findBySlug($payload->getSlug());
        if ($existing !== null) {
            throw new ConflictException('Role with this slug already exists');
        }

        $role = new RoleResource();
        $role->slug = $payload->getSlug();
        $role->name = $payload->getName();
        $role->description = $payload->getDescription();
        $role->is_system = false;

        try {
            $this->roleRepo->save($role);
        } catch (\Throwable $e) {
            if ($this->isDuplicateKey($e)) {
                throw new ConflictException('Role with this slug already exists');
            }
            throw $e;
        }

        $domain = $role->toDomain();

        $resource->setStatusCode(201);
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

    private function isDuplicateKey(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'duplicate')
            || str_contains($message, '1062')
            || str_contains($message, '23000');
    }
}
