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
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\RoleRepository;
use Semitexa\Platform\User\Application\Payload\Request\RoleListPayload;

#[AsPayloadHandler(payload: RoleListPayload::class, resource: GenericResponse::class)]
final class RoleListHandler implements HandlerInterface
{
    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        $roleResources = OrmManager::run(function (OrmManager $orm) {
            $repo = new RoleRepository($orm->getAdapter());
            return $repo->findAll();
        });

        $roles = [];
        foreach ($roleResources as $r) {
            $domain = $r->toDomain();
            $roles[] = [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'name' => $domain->name,
                'description' => $domain->description,
                'is_system' => $domain->isSystem,
            ];
        }

        return Response::json(['roles' => $roles]);
    }
}
