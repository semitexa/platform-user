<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Authorization\Attributes\RequiresPermission;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/users/{id}/roles', methods: ['PUT'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
#[RequiresPermission('roles.manage')]
class UserRolesSetPayload
{
    public string $id = '';
    protected array $role_ids = [];

    public function setId(string $id): void { $this->id = $id; }

    public function getRoleIds(): array { return $this->role_ids; }
    public function setRole_ids(array $ids): void { $this->role_ids = $ids; }
}
