<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\Request;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Payload\Request\UserGetPayload;
use Semitexa\Platform\User\Application\Resource\PlatformUserRepository;

#[AsPayloadHandler(payload: UserGetPayload::class, resource: GenericResponse::class)]
final class UserGetHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserGetPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $result = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            return $repo->findById($payload->id);
        });

        if ($result === null) {
            return Response::json(['error' => 'User not found'], 404);
        }

        $domain = $result->toDomain();

        return Response::json([
            'user' => [
                'id' => $domain->id,
                'email' => $domain->email,
                'name' => $domain->name,
                'is_active' => $domain->isActive,
            ],
        ]);
    }
}
