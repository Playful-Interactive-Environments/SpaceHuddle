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
}
