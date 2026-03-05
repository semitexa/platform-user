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
use Semitexa\Platform\User\Application\Payload\Request\ProfileFieldDeletePayload;
use Semitexa\Platform\User\Domain\Service\ProfileFieldServiceInterface;
use Semitexa\Platform\User\Domain\Service\ProfileValueServiceInterface;

#[AsPayloadHandler(payload: ProfileFieldDeletePayload::class, resource: GenericResponse::class)]
final class ProfileFieldDeleteHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected ProfileFieldServiceInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueServiceInterface $profileValueService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof ProfileFieldDeletePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $field = $this->profileFieldService->findById($payload->id);

        if ($field === null) {
            return Response::json(['error' => 'Profile field not found'], 404);
        }

        $this->profileValueService->deleteByFieldId($payload->id);
        $this->profileFieldService->delete($field);

        return Response::json(['success' => true]);
    }
}
