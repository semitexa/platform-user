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
use Semitexa\Platform\User\Application\Payload\Request\ProfileFieldListPayload;
use Semitexa\Platform\User\Domain\Repository\ProfileFieldRepositoryInterface;

#[AsPayloadHandler(payload: ProfileFieldListPayload::class, resource: GenericResponse::class)]
final class ProfileFieldListHandler implements HandlerInterface
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

        $fields = [];
        foreach ($this->profileFieldService->findAll() as $field) {
            $fields[] = [
                'id' => $field->id,
                'slug' => $field->slug,
                'label' => $field->label,
                'type' => $field->type,
                'is_required' => $field->isRequired,
                'sort_order' => $field->sortOrder,
                'options' => $field->options,
                'is_visible' => $field->isVisible,
                'icon' => $field->icon,
            ];
        }

        return Response::json(['fields' => $fields]);
    }
}
