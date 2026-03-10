<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\HandlerInterface;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ResourceInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Response;
use Semitexa\Platform\User\Application\Db\MySQL\Model\ProfileFieldResource;
use Semitexa\Platform\User\Application\Payload\Request\ProfileFieldCreatePayload;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;

#[AsPayloadHandler(payload: ProfileFieldCreatePayload::class, resource: GenericResponse::class)]
final class ProfileFieldCreateHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected ProfileFieldRepositoryInterface $profileFieldService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof ProfileFieldCreatePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $existing = $this->profileFieldService->findBySlug($payload->getSlug());
        if ($existing !== null) {
            return Response::json(['error' => 'A profile field with this slug already exists'], 409);
        }

        $field = new ProfileFieldResource();
        $field->slug = $payload->getSlug();
        $field->label = $payload->getLabel();
        $field->type = $payload->getType();
        $field->is_required = $payload->getIsRequired() ?? false;
        $field->sort_order = $payload->getSortOrder() ?? 0;
        $field->options = $payload->getOptions() !== null ? json_encode($payload->getOptions()) : null;
        $field->is_visible = $payload->getIsVisible() ?? true;
        $field->icon = $payload->getIcon();

        $this->profileFieldService->save($field);

        $domain = $field->toDomain();

        return Response::json([
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
        ], 201);
    }
}
