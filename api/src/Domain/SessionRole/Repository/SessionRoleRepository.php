<?php

namespace App\Domain\SessionRole\Repository;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\SessionRole\Data\SessionRoleData;
use App\Factory\QueryFactory;

/**
 * Repository.
 */
class SessionRoleRepository implements RepositoryInterface
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
            "session_role",
            SessionRoleData::class,
            "session_id",
            SessionRepository::class
        );
    }
}
