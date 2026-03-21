<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Authorization\Attributes\RequiresPermission;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Validation\Trait\NotBlankValidationTrait;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(path: '/api/platform/profile-fields', methods: ['POST'], responseWith: GenericResponse::class)]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
#[RequiresPermission('profile-fields.manage')]
class ProfileFieldCreatePayload implements ValidatablePayload
{
    use NotBlankValidationTrait;

    protected string $slug = '';
    protected string $label = '';
    protected string $type = 'text';
    protected ?bool $is_required = null;
    protected ?int $sort_order = null;
    protected ?array $options = null;
    protected ?bool $is_visible = null;
    protected ?string $icon = null;

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): void { $this->slug = $slug; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): void { $this->label = $label; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): void { $this->type = $type; }

    public function getIsRequired(): ?bool { return $this->is_required; }
    public function setIsRequired(bool $is_required): void { $this->is_required = $is_required; }

    public function getSortOrder(): ?int { return $this->sort_order; }
    public function setSortOrder(int $sort_order): void { $this->sort_order = $sort_order; }

    public function getOptions(): ?array { return $this->options; }
    public function setOptions(array $options): void { $this->options = $options; }

    public function getIsVisible(): ?bool { return $this->is_visible; }
    public function setIsVisible(bool $is_visible): void { $this->is_visible = $is_visible; }

    public function getIcon(): ?string { return $this->icon; }
    public function setIcon(string $icon): void { $this->icon = $icon; }

    public function validate(): PayloadValidationResult
    {
        $errors = [];
        $this->validateNotBlank('slug', $this->slug, $errors);
        $this->validateNotBlank('label', $this->label, $errors);

        if ($this->slug !== '' && !preg_match('/^[a-z0-9_]+$/', $this->slug)) {
            $errors['slug'] = 'Slug must contain only lowercase letters, digits, and underscores.';
        }

        $allowedTypes = ['text', 'textarea', 'select', 'file', 'url', 'date'];
        if (!in_array($this->type, $allowedTypes, true)) {
            $errors['type'] = 'Type must be one of: ' . implode(', ', $allowedTypes) . '.';
        }

        return new PayloadValidationResult(empty($errors), $errors);
    }
}
