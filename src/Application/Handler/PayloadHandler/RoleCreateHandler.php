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
use Semitexa\Platform\User\Application\Db\MySQL\Model\RoleResource;
use Semitexa\Platform\User\Application\Payload\Request\RoleCreatePayload;
use Semitexa\Platform\User\Domain\Repository\RoleRepositoryInterface;

#[AsPayloadHandler(payload: RoleCreatePayload::class, resource: GenericResponse::class)]
final class RoleCreateHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected RoleRepositoryInterface $roleRepo;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RoleCreatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $existing = $this->roleRepo->findBySlug($payload->getSlug());
        if ($existing !== null) {
            return Response::json(['error' => 'Role with this slug already exists'], 409);
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
                return Response::json(['error' => 'Role with this slug already exists'], 409);
            }
            throw $e;
        }

        $domain = $role->toDomain();

        return Response::json([
            'role' => [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'name' => $domain->name,
                'description' => $domain->description,
                'is_system' => $domain->isSystem,
            ],
        ], 201);
    }

    private function isDuplicateKey(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'duplicate')
            || str_contains($message, '1062')
            || str_contains($message, '23000');
    }
}
