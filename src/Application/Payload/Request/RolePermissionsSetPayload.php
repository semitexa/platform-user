<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Attributes\RequiresPermission;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/roles/{id}/permissions', methods: ['PUT'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[RequiresAuth]
#[RequiresPermission('roles.manage')]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
class RolePermissionsSetPayload implements PayloadInterface
{
    public string $id = '';
    protected array $permission_ids = [];

    public function setId(string $id): void { $this->id = $id; }

    public function getPermissionIds(): array { return $this->permission_ids; }
    public function setPermission_ids(array $ids): void { $this->permission_ids = $ids; }
}
