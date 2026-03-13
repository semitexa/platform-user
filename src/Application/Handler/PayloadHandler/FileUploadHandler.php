<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\ValidationException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\FileUploadPayload;
use Semitexa\Platform\User\Domain\Service\FileStorageServiceInterface;

#[AsPayloadHandler(payload: FileUploadPayload::class, resource: GenericResponse::class)]
final class FileUploadHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected FileStorageServiceInterface $fileStorageService;

    public function handle(FileUploadPayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $contents = base64_decode($payload->getContents(), true);

        if ($contents === false) {
            throw new ValidationException(['contents' => ['Invalid base64 contents']]);
        }

        $userId = $this->auth->getUser()->getId();

        $file = $this->fileStorageService->upload(
            $contents,
            $payload->getName(),
            $payload->getMimeType(),
            $userId,
        );

        $resource->setStatusCode(201);
        $resource->setContext([
            'file' => [
                'id' => $file->id,
                'original_name' => $file->originalName,
                'mime_type' => $file->mimeType,
                'size' => $file->size,
                'url' => '/api/platform/files/' . $file->id,
            ],
        ]);
        return $resource;
    }
}
