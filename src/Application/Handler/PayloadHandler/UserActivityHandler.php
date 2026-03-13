<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\UserActivityPayload;
use Semitexa\Platform\User\Domain\Repository\UserActivityRepositoryInterface;

#[AsPayloadHandler(payload: UserActivityPayload::class, resource: GenericResponse::class)]
final class UserActivityHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserActivityRepositoryInterface $activityService;

    public function handle(UserActivityPayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $activity = [];
        foreach ($this->activityService->findByUserId($payload->id) as $a) {
            $activity[] = [
                'id' => $a->id,
                'action' => $a->action,
                'ip_address' => $a->ipAddress,
                'user_agent' => $a->userAgent,
                'created_at' => $a->createdAt?->format(\DateTimeInterface::ATOM),
            ];
        }

        $resource->setContext(['activity' => $activity]);
        return $resource;
    }
}
