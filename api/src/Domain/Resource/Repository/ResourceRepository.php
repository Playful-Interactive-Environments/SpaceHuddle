<?php

namespace App\Domain\Resource\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Resource\Data\ResourceData;
use App\Domain\Session\Repository\SessionRepository;
use App\Factory\QueryFactory;

/**
 * Repository.
 */
class ResourceRepository implements RepositoryInterface
{
    use RepositoryTrait;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "resource",
            ResourceData::class,
            "session_id",
            SessionRepository::class
        );
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "id" => $data->id ?? null,
            "session_id" => $data->sessionId ?? null,
            "title" => $data->title ?? null,
            "image" => $data->image ?? null,
            "link" => $data->link ?? null
        ];
    }

    /**
     * List of columns to be cloned
     * @return array<string> The array
     */
    protected function cloneColumns(): array
    {
        return [
            "title",
            "image",
            "link"
        ];
    }
}
