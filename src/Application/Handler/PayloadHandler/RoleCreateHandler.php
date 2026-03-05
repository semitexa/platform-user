<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Model\RoleResource;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\RoleRepository;
use Semitexa\Platform\User\Application\Payload\Request\RoleCreatePayload;

#[AsPayloadHandler(payload: RoleCreatePayload::class, resource: GenericResponse::class)]
final class RoleCreateHandler implements HandlerInterface
{
    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RoleCreatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $role = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new RoleRepository($orm->getAdapter());

            $existing = $repo->findBySlug($payload->getSlug());
            if ($existing !== null) {
                return null;
            }

            $role = new RoleResource();
            $role->slug = $payload->getSlug();
            $role->name = $payload->getName();
            $role->description = $payload->getDescription();
            $role->is_system = false;

            $repo->save($role);

            return $role;
        });

        if ($role === null) {
            return Response::json(['error' => 'Role with this slug already exists'], 409);
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
}
