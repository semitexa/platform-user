<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Attributes\RequiresPermission;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Validation\Trait\NotBlankValidationTrait;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/roles', methods: ['POST'], responseWith: GenericResponse::class)]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
#[RequiresAuth]
#[RequiresPermission('roles.manage')]
class RoleCreatePayload implements ValidatablePayload
{
    use NotBlankValidationTrait;

    protected string $slug = '';
    protected string $name = '';
    protected ?string $description = null;

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): void { $this->slug = $slug; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): void { $this->description = $description; }

    public function validate(): PayloadValidationResult
    {
        $errors = [];
        $this->validateNotBlank('slug', $this->slug, $errors);
        $this->validateNotBlank('name', $this->name, $errors);
        return new PayloadValidationResult(empty($errors), $errors);
    }
}
