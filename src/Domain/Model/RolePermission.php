<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Domain\Model;

final readonly class RolePermission
{
    public function __construct(
        public string $id,
        public string $roleId,
        public string $permissionId,
    ) {}
}
