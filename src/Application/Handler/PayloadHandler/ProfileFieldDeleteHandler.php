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
use Semitexa\Platform\User\Application\Payload\Request\ProfileFieldDeletePayload;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileValueRepositoryInterface;

#[AsPayloadHandler(payload: ProfileFieldDeletePayload::class, resource: GenericResponse::class)]
final class ProfileFieldDeleteHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected ProfileFieldRepositoryInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueRepositoryInterface $profileValueService;

    public function handle(ProfileFieldDeletePayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $field = $this->profileFieldService->findById($payload->id);

        if ($field === null) {
            throw new NotFoundException('Profile field', $payload->id);
        }

        $this->profileValueService->deleteByFieldId($payload->id);
        $this->profileFieldService->delete($field);

        $resource->setContext(['success' => true]);
        return $resource;
    }
}
