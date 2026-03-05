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
use Semitexa\Platform\User\Application\Payload\Request\RoleDeletePayload;

#[AsPayloadHandler(payload: RoleDeletePayload::class, resource: GenericResponse::class)]
final class RoleDeleteHandler implements HandlerInterface
{
    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if (!$payload instanceof RoleDeletePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $result = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new RoleRepository($orm->getAdapter());
            $role = $repo->findById($payload->id);

            if ($role === null) {
                return 'not_found';
            }

            if ($role->is_system) {
                return 'system_role';
            }

            $repo->delete($role);

            return 'deleted';
        });

        if ($result === 'not_found') {
            return Response::json(['error' => 'Role not found'], 404);
        }

        if ($result === 'system_role') {
            return Response::json(['error' => 'Cannot delete a system role'], 403);
        }

        return Response::json(['success' => true]);
    }
}
