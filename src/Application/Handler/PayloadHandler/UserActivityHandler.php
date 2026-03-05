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
use Semitexa\Platform\User\Domain\Service\UserActivityServiceInterface;

#[AsPayloadHandler(payload: UserActivityPayload::class, resource: GenericResponse::class)]
final class UserActivityHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserActivityServiceInterface $activityService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserActivityPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $resources = $this->activityService->findByUserId($payload->id);

        $activity = [];
        foreach ($resources as $r) {
            $domain = $r->toDomain();
            $activity[] = [
                'id' => $domain->id,
                'action' => $domain->action,
                'ip_address' => $domain->ipAddress,
                'user_agent' => $domain->userAgent,
                'created_at' => $domain->createdAt?->format(\DateTimeInterface::ATOM),
            ];
        }

        return Response::json(['activity' => $activity]);
    }
}
