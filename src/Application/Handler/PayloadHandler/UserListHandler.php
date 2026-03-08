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
use Semitexa\Platform\User\Application\Payload\Request\UserListPayload;
use Semitexa\Platform\User\Domain\Repository\UserRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;
use Semitexa\Platform\User\Domain\Repository\ProfileValueRepositoryInterface;
use Semitexa\Platform\User\Domain\Service\RbacServiceInterface;
use Semitexa\Platform\User\Domain\Repository\UserActivityRepositoryInterface;

#[AsPayloadHandler(payload: UserListPayload::class, resource: GenericResponse::class)]
final class UserListHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected UserRepositoryInterface $userRepo;

    #[InjectAsReadonly]
    protected RbacServiceInterface $rbacService;

    #[InjectAsReadonly]
    protected UserActivityRepositoryInterface $activityService;

    #[InjectAsReadonly]
    protected ProfileFieldRepositoryInterface $profileFieldService;

    #[InjectAsReadonly]
    protected ProfileValueRepositoryInterface $profileValueService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof UserListPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $search = $payload->getSearch();

        if ($search !== null && $search !== '') {
            $resources = $this->userRepo->search($search, $payload->getLimit());
        } else {
            $resources = $this->userRepo->findAll($payload->getLimit());
        }

        $fieldResources = $this->profileFieldService->findAll();
        $requiredFieldIds = [];
        $avatarFieldId = null;

        foreach ($fieldResources as $fr) {
            $fd = $fr->toDomain();
            if ($fd->isRequired) {
                $requiredFieldIds[] = $fd->id;
            }
            if ($fd->slug === 'avatar') {
                $avatarFieldId = $fd->id;
            }
        }

        $users = [];
        foreach ($resources as $r) {
            $domain = $r->toDomain();
            $userId = $domain->id;

            $avatarUrl = null;
            if ($avatarFieldId !== null) {
                $avatarValue = $this->profileValueService->findByUserAndField($userId, $avatarFieldId);
                if ($avatarValue !== null && $avatarValue->file_id !== null) {
                    $avatarUrl = '/api/platform/files/' . $avatarValue->file_id;
                }
            }

            $lastLogin = $this->activityService->getLastLoginForUser($userId);

            $valueResources = $this->profileValueService->findByUserId($userId);
            $filledRequired = 0;
            foreach ($valueResources as $v) {
                $vDomain = $v->toDomain();
                if (in_array($vDomain->fieldId, $requiredFieldIds, true) && ($vDomain->value !== null || $vDomain->fileId !== null)) {
                    $filledRequired++;
                }
            }

            $completeness = count($requiredFieldIds) > 0
                ? round(($filledRequired / count($requiredFieldIds)) * 100, 1)
                : 100.0;

            $roles = [];
            foreach ($this->rbacService->getUserRoles($userId) as $role) {
                $roles[] = [
                    'id' => $role->id,
                    'slug' => $role->slug,
                    'name' => $role->name,
                ];
            }

            $users[] = [
                'id' => $domain->id,
                'email' => $domain->email,
                'name' => $domain->name,
                'is_active' => $domain->isActive,
                'avatar_url' => $avatarUrl,
                'last_login' => $lastLogin?->toDomain()->createdAt?->format(\DateTimeInterface::ATOM),
                'profile_completeness' => $completeness,
                'roles' => $roles,
            ];
        }

        return Response::json(['users' => $users]);
    }
}
