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
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PermissionRepository;
use Semitexa\Platform\User\Application\Payload\Request\PermissionListPayload;

#[AsPayloadHandler(payload: PermissionListPayload::class, resource: GenericResponse::class)]
final class PermissionListHandler implements HandlerInterface
{
    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        $permResources = OrmManager::run(function (OrmManager $orm) {
            $repo = new PermissionRepository($orm->getAdapter());
            return $repo->findAll();
        });

        $permissions = [];
        foreach ($permResources as $r) {
            $domain = $r->toDomain();
            $permissions[] = [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'name' => $domain->name,
                'group_key' => $domain->groupKey,
            ];
        }

        return Response::json(['permissions' => $permissions]);
    }
}
