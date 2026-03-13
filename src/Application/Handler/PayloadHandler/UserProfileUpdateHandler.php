<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileValueResource;
use Semitexa\Platform\User\Application\Payload\Request\UserProfileUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileValueRepositoryInterface;

#[AsPayloadHandler(payload: UserProfileUpdatePayload::class, resource: GenericResponse::class)]
final class UserProfileUpdateHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    #[InjectAsReadonly]
    protected ProfileFieldRepositoryInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueRepositoryInterface $profileValueService;

    public function handle(UserProfileUpdatePayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $userResource = $this->userRepo->findById($payload->id);

        if ($userResource === null) {
            throw new NotFoundException('User', $payload->id);
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

        $resource->setContext([
            'success' => true,
            'fields' => $updatedFields,
        ]);
        return $resource;
    }
}
