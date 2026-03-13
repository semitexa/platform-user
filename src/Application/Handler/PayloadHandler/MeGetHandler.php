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
use Semitexa\Platform\User\Application\Payload\Request\MeGetPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileValueRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;
use Semitexa\Platform\User\Domain\Repository\UserActivityRepositoryInterface;

#[AsPayloadHandler(payload: MeGetPayload::class, resource: GenericResponse::class)]
final class MeGetHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    #[InjectAsReadonly]
    protected ProfileFieldRepositoryInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueRepositoryInterface $profileValueService;

    #[InjectAsReadonly]
    protected RbacServiceInterface $rbacService;

    #[InjectAsReadonly]
    protected UserActivityRepositoryInterface $activityService;

    public function handle(MeGetPayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $userId = $this->auth->getUser()->getId();

        $userResource = $this->userRepo->findById($userId);

        if ($userResource === null) {
            throw new NotFoundException('User', $userId);
        }

        $user = $userResource->toDomain();

        $profileFields = $this->profileFieldService->findAll(null);
        $profileValues = $this->profileValueService->findByUserId($userId);

        $valuesByFieldId = [];
        foreach ($profileValues as $v) {
            $valuesByFieldId[$v->fieldId] = $v;
        }

        $fields = [];
        $requiredCount = 0;
        $filledRequired = 0;

        foreach ($profileFields as $fd) {
            $value = $valuesByFieldId[$fd->id] ?? null;

            $fields[] = [
                'slug' => $fd->slug,
                'label' => $fd->label,
                'type' => $fd->type,
                'value' => $value?->value,
                'file_id' => $value?->fileId,
            ];

            if ($fd->isRequired) {
                $requiredCount++;
                if ($value !== null && ($value->value !== null || $value->fileId !== null)) {
                    $filledRequired++;
                }
            }
        }

        $completeness = $requiredCount > 0 ? round(($filledRequired / $requiredCount) * 100, 1) : 100.0;

        $roles = [];
        foreach ($this->rbacService->getUserRoles($userId) as $role) {
            $roles[] = [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
            ];
        }

        $lastLogin = $this->activityService->getLastLoginForUser($userId);

        $resource->setContext([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'fields' => $fields,
            'roles' => $roles,
            'profile_completeness' => $completeness,
            'last_login' => $lastLogin?->createdAt?->format(\DateTimeInterface::ATOM),
        ]);
        return $resource;
    }
}
