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
use Semitexa\Platform\User\Application\Payload\Request\FileServePayload;
use Semitexa\Platform\User\Domain\Service\FileStorageServiceInterface;

#[AsPayloadHandler(payload: FileServePayload::class, resource: GenericResponse::class)]
final class FileServeHandler implements HandlerInterface
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

        if (!$payload instanceof FileServePayload) {
            return Response::json(['error' => 'Invalid payload'], 400);
        }

        $file = $this->fileStorageService->findById($payload->id);

        if ($file === null) {
            return Response::json(['error' => 'File not found'], 404);
        }

        if ($file->uploadedBy !== $this->auth->getUser()->getId()) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $contents = $this->fileStorageService->getContents($file);

        if ($contents === null) {
            return Response::json(['error' => 'File contents unavailable'], 404);
        }

        return (new Response($contents, 200, [
            'Content-Type' => $file->mimeType,
            'Content-Disposition' => 'inline; filename="' . str_replace(['"', "\r", "\n"], '', $file->originalName) . '"',
            'Cache-Control' => 'private, max-age=86400',
        ]));
    }
}
