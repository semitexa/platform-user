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
use Semitexa\Platform\User\Application\Payload\Request\UserCreatePayload;
use Semitexa\Platform\User\Application\Resource\PlatformUserRepository;
use Semitexa\Platform\User\Application\Resource\PlatformUserResource;

#[AsPayloadHandler(payload: UserCreatePayload::class, resource: GenericResponse::class)]
final class UserCreateHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserCreatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $result = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new PlatformUserRepository($orm->getAdapter());

            $existing = $repo->findByEmail($payload->getEmail());
            if ($existing !== null) {
                return ['error' => 'Email already exists'];
            }

            $user = new PlatformUserResource();
            $user->email = $payload->getEmail();
            $user->name = $payload->getName();
            $user->password_hash = password_hash($payload->getPassword(), PASSWORD_DEFAULT);
            $user->is_active = true;

            $repo->save($user);

            $domain = $user->toDomain();

            return [
                'user' => [
                    'id' => $domain->id,
                    'email' => $domain->email,
                    'name' => $domain->name,
                    'is_active' => $domain->isActive,
                ],
            ];
        });

        if (isset($result['error'])) {
            return Response::json($result, 409);
        }

        return Response::json($result, 201);
    }
}
