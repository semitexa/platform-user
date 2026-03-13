<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\ProfileFieldUpdatePayload;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;

#[AsPayloadHandler(payload: ProfileFieldUpdatePayload::class, resource: GenericResponse::class)]
final class ProfileFieldUpdateHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected ProfileFieldRepositoryInterface $profileFieldService;

    public function handle(ProfileFieldUpdatePayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $field = $this->profileFieldService->findById($payload->id);

        if ($field === null) {
            throw new NotFoundException('Profile field', $payload->id);
        }

        if ($payload->getSlug() !== null) {
            $field->slug = $payload->getSlug();
        }
        if ($payload->getLabel() !== null) {
            $field->label = $payload->getLabel();
        }
        if ($payload->getType() !== null) {
            $field->type = $payload->getType();
        }
        if ($payload->getIsRequired() !== null) {
            $field->is_required = $payload->getIsRequired();
        }
        if ($payload->getSortOrder() !== null) {
            $field->sort_order = $payload->getSortOrder();
        }
        if ($payload->getOptions() !== null) {
            $field->options = json_encode($payload->getOptions());
        }
        if ($payload->getIsVisible() !== null) {
            $field->is_visible = $payload->getIsVisible();
        }
        if ($payload->getIcon() !== null) {
            $field->icon = $payload->getIcon();
        }

        $this->profileFieldService->save($field);

        $domain = $field->toDomain();

        $resource->setContext([
            'field' => [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'label' => $domain->label,
                'type' => $domain->type,
                'is_required' => $domain->isRequired,
                'sort_order' => $domain->sortOrder,
                'options' => $domain->options,
                'is_visible' => $domain->isVisible,
                'icon' => $domain->icon,
            ],
        ]);
        return $resource;
    }
}
