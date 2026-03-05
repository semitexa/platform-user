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
use Semitexa\Platform\User\Application\Db\MySQL\Repository\RoleRepository;
use Semitexa\Platform\User\Application\Payload\Request\RoleUpdatePayload;

#[AsPayloadHandler(payload: RoleUpdatePayload::class, resource: GenericResponse::class)]
final class RoleUpdateHandler implements HandlerInterface
{
    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RoleUpdatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $role = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new RoleRepository($orm->getAdapter());
            $role = $repo->findById($payload->id);

            if ($role === null) {
                return null;
            }

            if ($payload->getName() !== null) {
                $role->name = $payload->getName();
            }

            if ($payload->getDescription() !== null) {
                $role->description = $payload->getDescription();
            }

            $repo->save($role);

            return $role;
        });

        if ($role === null) {
            return Response::json(['error' => 'Role not found'], 404);
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
        ]);
    }
}
