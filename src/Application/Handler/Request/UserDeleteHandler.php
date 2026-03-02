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
use Semitexa\Platform\User\Application\Payload\Request\UserDeletePayload;
use Semitexa\Platform\User\Application\Resource\PlatformUserRepository;

#[AsPayloadHandler(payload: UserDeletePayload::class, resource: GenericResponse::class)]
final class UserDeleteHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserDeletePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $result = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            $user = $repo->findById($payload->id);

            if ($user === null) {
                return false;
            }

            $repo->delete($user);
            return true;
        });

        if (!$result) {
            return Response::json(['error' => 'User not found'], 404);
        }

        return Response::json(['success' => true]);
    }
}
