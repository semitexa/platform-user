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
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;
use Semitexa\Platform\User\Application\Payload\Request\UserProfileUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\ProfileFieldServiceInterface;
use Semitexa\Platform\User\Domain\Service\ProfileValueServiceInterface;

#[AsPayloadHandler(payload: UserProfileUpdatePayload::class, resource: GenericResponse::class)]
final class UserProfileUpdateHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    #[InjectAsReadonly]
    protected ProfileFieldServiceInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueServiceInterface $profileValueService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserProfileUpdatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $userResource = $this->userRepo->findById($payload->id);

        if ($userResource === null) {
            return Response::json(['error' => 'User not found'], 404);
        }

        $fieldsInput = $payload->getFields();
        $updatedFields = [];

        foreach ($fieldsInput as $slug => $rawValue) {
            $fieldResource = $this->profileFieldService->findBySlug($slug);

            if ($fieldResource === null) {
                continue;
            }

            $fieldDomain = $fieldResource->toDomain();

            $valueResource = $this->profileValueService->findByUserAndField($payload->id, $fieldDomain->id);

            if ($valueResource === null) {
                $valueResource = new ProfileValueResource();
                $valueResource->user_id = $payload->id;
                $valueResource->field_id = $fieldDomain->id;
            }

            $fileId = null;
            if (is_string($rawValue) && str_starts_with($rawValue, 'file:')) {
                $fileId = substr($rawValue, 5);
                $valueResource->value = null;
                $valueResource->file_id = $fileId;
            } else {
                $valueResource->value = is_string($rawValue) ? $rawValue : null;
                $valueResource->file_id = null;
            }

            $this->profileValueService->save($valueResource);

            $updatedFields[] = [
                'slug' => $slug,
                'label' => $fieldDomain->label,
                'type' => $fieldDomain->type,
                'value' => $valueResource->value,
                'file_id' => $fileId,
            ];
        }

        return Response::json([
            'success' => true,
            'fields' => $updatedFields,
        ]);
    }
}
