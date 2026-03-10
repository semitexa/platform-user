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
use Semitexa\Platform\User\Application\Payload\Request\FileUploadPayload;
use Semitexa\Platform\User\Domain\Service\FileStorageServiceInterface;

#[AsPayloadHandler(payload: FileUploadPayload::class, resource: GenericResponse::class)]
final class FileUploadHandler implements HandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected FileStorageServiceInterface $fileStorageService;

    public function handle(PayloadInterface $payload, ResourceInterface $resource): ResourceInterface
    {
        if ($this->auth->isGuest()) {
            return Response::json(['error' => 'Unauthorized'], 401);
        }

        if (!$payload instanceof FileUploadPayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $contents = base64_decode($payload->getContents(), true);

        if ($contents === false) {
            return Response::json(['error' => 'Invalid base64 contents'], 400);
        }

        $userId = $this->auth->getUser()->getId();

        $file = $this->fileStorageService->upload(
            $contents,
            $payload->getName(),
            $payload->getMimeType(),
            $userId,
        );

        return Response::json([
            'file' => [
                'id' => $file->id,
                'original_name' => $file->originalName,
                'mime_type' => $file->mimeType,
                'size' => $file->size,
                'url' => '/api/platform/files/' . $file->id,
            ],
        ], 201);
    }
}
