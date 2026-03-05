<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Attributes\RequiresPermission;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Http\Response\GenericResponse;

#[AsPayload(path: '/api/platform/profile-fields/{id}', methods: ['PATCH'], responseWith: GenericResponse::class, requirements: ['id' => '[a-f0-9\\-]{36}'])]
#[RequiresAuth]
#[RequiresPermission('profile-fields.manage')]
class ProfileFieldUpdatePayload implements PayloadInterface
{
    public string $id = '';
    protected ?string $slug = null;
    protected ?string $label = null;
    protected ?string $type = null;
    protected ?bool $is_required = null;
    protected ?int $sort_order = null;
    protected ?array $options = null;
    protected ?bool $is_visible = null;
    protected ?string $icon = null;

    public function setId(string $id): void { $this->id = $id; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): void { $this->slug = $slug; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(string $label): void { $this->label = $label; }

    public function getType(): ?string { return $this->type; }
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
}
