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
use Semitexa\Platform\User\Domain\Service\ProfileFieldServiceInterface;

#[AsPayloadHandler(payload: ProfileFieldListPayload::class, resource: GenericResponse::class)]
final class ProfileFieldListHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected ProfileFieldServiceInterface $profileFieldService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        $resources = $this->profileFieldService->findAll();

        $fields = [];
        foreach ($resources as $r) {
            $domain = $r->toDomain();
            $fields[] = [
                'id' => $domain->id,
                'slug' => $domain->slug,
                'label' => $domain->label,
                'type' => $domain->type,
                'is_required' => $domain->isRequired,
                'sort_order' => $domain->sortOrder,
                'options' => $domain->options,
                'is_visible' => $domain->isVisible,
                'icon' => $domain->icon,
            ];
        }

        return Response::json(['fields' => $fields]);
    }
}
