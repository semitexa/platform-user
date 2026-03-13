<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Handler\PayloadHandler;

use Semitexa\Core\Attributes\AsPayloadHandler;
use Semitexa\Core\Attributes\InjectAsReadonly;
use Semitexa\Core\Auth\AuthContextInterface;
use Semitexa\Core\Contract\TypedHandlerInterface;
use Semitexa\Core\Exception\AccessDeniedException;
use Semitexa\Core\Exception\AuthenticationException;
use Semitexa\Core\Exception\NotFoundException;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Platform\User\Application\Payload\Request\FileServePayload;
use Semitexa\Platform\User\Domain\Service\FileStorageServiceInterface;

#[AsPayloadHandler(payload: FileServePayload::class, resource: GenericResponse::class)]
final class FileServeHandler implements TypedHandlerInterface
{
    #[InjectAsReadonly]
    protected AuthContextInterface $auth;

    #[InjectAsReadonly]
    protected FileStorageServiceInterface $fileStorageService;

    public function handle(FileServePayload $payload, GenericResponse $resource): GenericResponse
    {
        if ($this->auth->isGuest()) {
            throw new AuthenticationException();
        }

        $file = $this->fileStorageService->findById($payload->id);

        if ($file === null) {
            throw new NotFoundException('File', $payload->id);
        }

        if ($file->uploadedBy !== $this->auth->getUser()->getId()) {
            throw new AccessDeniedException('You do not have access to this file.');
        }

        $contents = $this->fileStorageService->getContents($file);

        if ($contents === null) {
            throw new NotFoundException('File contents', $payload->id);
        }

        $resource->setContent($contents);
        $resource->setHeader('Content-Type', $file->mimeType);
        $resource->setHeader('Content-Disposition', 'inline; filename="' . str_replace(['"', "\r", "\n"], '', $file->originalName) . '"');
        $resource->setHeader('Cache-Control', 'private, max-age=86400');
        return $resource;
    }
}
