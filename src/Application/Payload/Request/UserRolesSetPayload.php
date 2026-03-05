<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Attributes\RequiresPermission;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Http\Response\GenericResponse;

#[AsPayload(path: '/api/platform/users/{id}/roles', methods: ['PUT'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[RequiresAuth]
#[RequiresPermission('roles.manage')]
class UserRolesSetPayload implements PayloadInterface
{
    public string $id = '';
    protected array $role_ids = [];

    public function setId(string $id): void { $this->id = $id; }

    public function getRoleIds(): array { return $this->role_ids; }
    public function setRole_ids(array $ids): void { $this->role_ids = $ids; }
}
