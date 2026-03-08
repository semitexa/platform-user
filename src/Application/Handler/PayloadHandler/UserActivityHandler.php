<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Platform\User\Application\Payload\Request\UserActivityPayload;
use Semitexa\Platform\User\Domain\Repository\UserActivityRepositoryInterface;

#[AsPayloadHandler(payload: UserActivityPayload::class, resource: GenericResponse::class)]
final class UserActivityHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserActivityRepositoryInterface $activityService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserActivityPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
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

        return Response::json(['activity' => $activity]);
    }
}
