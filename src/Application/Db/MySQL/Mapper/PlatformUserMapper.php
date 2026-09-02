<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Db\MySQL\Mapper;

use Semitexa\Orm\Attribute\AsMapper;
use Semitexa\Orm\Domain\Contract\ResourceModelMapperInterface;
use Semitexa\Platform\User\Application\Db\MySQL\Model\PlatformUserResourceModel;
use Semitexa\Platform\User\Domain\Enum\UserRole;
use Semitexa\Platform\User\Domain\Enum\UserStatus;
use Semitexa\Platform\User\Domain\Model\PlatformUser;

#[AsMapper(resourceModel: PlatformUserResourceModel::class, domainModel: PlatformUser::class)]
final class PlatformUserMapper implements ResourceModelMapperInterface
{
    public function toDomain(object $resourceModel): object
    {
        $resourceModel instanceof PlatformUserResourceModel || throw new \InvalidArgumentException('Unexpected resource model.');

        return new PlatformUser(
            id: $resourceModel->id,
            email: $resourceModel->email,
            passwordHash: $resourceModel->passwordHash,
            displayName: $resourceModel->displayName,
            tenantId: $resourceModel->tenantId,
            // A row whose role or status stopped matching the enum must not
            // silently become an owner or an active account: fall back to the
            // least privileged reading of it.
            role: UserRole::tryFrom($resourceModel->role) ?? UserRole::Editor,
            status: UserStatus::tryFrom($resourceModel->status) ?? UserStatus::Disabled,
            createdAt: $resourceModel->createdAt,
            updatedAt: $resourceModel->updatedAt,
            lastLoginAt: $resourceModel->lastLoginAt,
            failedAttempts: $resourceModel->failedAttempts,
            lockedUntil: $resourceModel->lockedUntil,
        );
    }

    public function toSourceModel(object $domainModel): object
    {
        $domainModel instanceof PlatformUser || throw new \InvalidArgumentException('Unexpected domain model.');

        return new PlatformUserResourceModel(
            id: $domainModel->getId(),
            email: $domainModel->getEmail(),
            passwordHash: $domainModel->getPasswordHash(),
            displayName: $domainModel->getDisplayName(),
            tenantId: $domainModel->getTenantId(),
            role: $domainModel->getRole()->value,
            status: $domainModel->getStatus()->value,
            createdAt: $domainModel->getCreatedAt(),
            updatedAt: $domainModel->getUpdatedAt(),
            lastLoginAt: $domainModel->getLastLoginAt(),
            failedAttempts: $domainModel->getFailedAttempts(),
            lockedUntil: $domainModel->getLockedUntil(),
        );
    }
}
