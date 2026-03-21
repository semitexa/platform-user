<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Authorization\Attributes\RequiresPermission;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/roles/{id}', methods: ['PATCH'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
#[RequiresPermission('roles.manage')]
class RoleUpdatePayload
{
    public string $id = '';
    protected ?string $name = null;
    protected ?string $description = null;

    public function setId(string $id): void { $this->id = $id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }
}
