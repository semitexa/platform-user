<?php

declare(strict_types=1);

namespace Semitexa\Platform\User\Application\Payload\Request;

use Semitexa\Core\Attributes\AsPayload;
use Semitexa\Core\Contract\PayloadInterface;
use Semitexa\Core\Http\Response\GenericResponse;
use Semitexa\Testing\Attributes\TestablePayload;
use Semitexa\Testing\Strategy\Profile\StandardProfileStrategy;

#[AsPayload(path: '/api/platform/users', methods: ['GET'], responseWith: GenericResponse::class)]
#[TestablePayload(strategies: [StandardProfileStrategy::class])]
class UserListPayload implements PayloadInterface
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
