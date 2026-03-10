<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Attributes\RequiresAuth;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Contract\ValidatablePayload;
use Semitexa\Core\Http\PayloadValidationResult;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Core\Validation\Trait\NotBlankValidationTrait;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\ParanoiaProfileStrategy;

#[AsPayload(
    responseWith: GenericResponse::class,
    path: '/api/platform/files',
    methods: ['POST']
)]
#[TestablePayload(strategies: [ParanoiaProfileStrategy::class])]
#[RequiresAuth]
class FileUploadPayload implements PayloadInterface, ValidatablePayload
{
    use NotBlankValidationTrait;

    protected string $name = '';
    protected string $mime_type = '';
    protected string $contents = '';

    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }

    public function getMimeType(): string { return $this->mime_type; }
    public function setMimeType(string $mime_type): void { $this->mime_type = $mime_type; }

    public function getContents(): string { return $this->contents; }
    public function setContents(string $contents): void { $this->contents = $contents; }

    public function validate(): PayloadValidationResult
    {
        $errors = [];
        $this->validateNotBlank('name', $this->name, $errors);
        $this->validateNotBlank('mime_type', $this->mime_type, $errors);
        $this->validateNotBlank('contents', $this->contents, $errors);

        return new PayloadValidationResult(empty($errors), $errors);
    }
}
