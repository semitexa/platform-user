<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Api\Attributes\ApiVersion;
use Semitexa\Api\Attributes\ExternalApi;
use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;

#[AsPayload(path: '/api/platform/users', methods: ['GET'], responseWith: GenericResponse::class)]
#[ExternalApi(version: 'v1', description: 'List platform users for machine clients.')]
#[ApiVersion(version: '1.0.0')]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
class UserListPayload
{
    protected int $limit = 50;
    protected int $offset = 0;
    protected ?string $search = null;

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = max(1, min(1000, $limit));
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = max(0, $offset);
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function setSearch(string $search): void
    {
        $this->search = $search;
    }
}
