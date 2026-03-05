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
use Semitexa\Platform\User\Application\Payload\Request\UserProfileGetPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\ProfileFieldServiceInterface;
use Semitexa\Platform\User\Domain\Service\ProfileValueServiceInterface;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;

#[AsPayloadHandler(payload: UserProfileGetPayload::class, resource: GenericResponse::class)]
final class UserProfileGetHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    #[InjectAsReadonly]
    protected ProfileFieldServiceInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueServiceInterface $profileValueService;

    #[InjectAsReadonly]
    protected RbacServiceInterface $rbacService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserProfileGetPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $userResource = $this->userRepo->findById($payload->id);

        if ($userResource === null) {
            return Response::json(['error' => 'User not found'], 404);
        }

        $user = $userResource->toDomain();

        $fieldResources = $this->profileFieldService->findAll();
        $valueResources = $this->profileValueService->findByUserId($payload->id);

        $valuesByFieldId = [];
        foreach ($valueResources as $v) {
            $vDomain = $v->toDomain();
            $valuesByFieldId[$vDomain->fieldId] = $vDomain;
        }

        $fields = [];
        $requiredCount = 0;
        $filledRequired = 0;

        foreach ($fieldResources as $fr) {
            $fd = $fr->toDomain();
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
        foreach ($this->rbacService->getUserRoles($payload->id) as $role) {
            $roles[] = [
                'id' => $role->id,
                'slug' => $role->slug,
                'name' => $role->name,
            ];
        }

        return Response::json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
            ],
            'fields' => $fields,
            'roles' => $roles,
            'profile_completeness' => $completeness,
        ]);
    }
}
