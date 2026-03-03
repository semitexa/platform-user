<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Event\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Orm\OrmManager;
use Semitexa\Platform\User\Application\Payload\Request\UserUpdatePayload;
use Semitexa\Platform\User\Application\Db\MySQL\Repository\PlatformUserRepository;

#[AsPayloadHandler(payload: UserUpdatePayload::class, resource: GenericResponse::class)]
final class UserUpdateHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserUpdatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $result = OrmManager::run(function (OrmManager $orm) use ($payload) {
            $repo = new PlatformUserRepository($orm->getAdapter());
            $user = $repo->findById($payload->id);

            if ($user === null) {
                return ['error' => 'User not found', 'status' => 404];
            }

            if ($payload->getEmail() !== null) {
                $existing = $repo->findByEmail($payload->getEmail());
                if ($existing !== null && $existing->id !== $user->id) {
                    return ['error' => 'Email already exists', 'status' => 409];
                }
                $user->email = $payload->getEmail();
            }

            if ($payload->getName() !== null) {
                $user->name = $payload->getName();
            }

            if ($payload->getPassword() !== null) {
                $user->password_hash = password_hash($payload->getPassword(), PASSWORD_DEFAULT);
            }

            if ($payload->getIsActive() !== null) {
                $user->is_active = $payload->getIsActive();
            }

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
            return Response::json(['error' => $result['error']], $result['status']);
        }

        return Response::json($result);
    }
}
