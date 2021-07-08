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
    use RepositoryTrait {
        RepositoryTrait::insert as private genericInsert;
    }

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

    /**
     * Evaluates whether the give username is valid.
     * @param string $username Username to be checked.
     * @return string|null User Id
     */
    public function getUserId(string $username): ?string
    {
        $query = $this->queryFactory->newSelect("user");
        $query->select(["id"])
            ->andWhere([
                "username" => $username
            ]);

        $result = $query->execute()->fetch("assoc");
        if (is_array($result)) {
            return $result["id"];
        }
        return null;
    }

    /**
     * Insert session row.
     * @param object $data The session data
     * @return object|null The new session
     */
    public function insert(object $data): ?object
    {
        $data->userId = $this->getUserId($data->username);
        return $this->genericInsert($data);
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function formatDatabaseInput(object $data): array
    {
        return [
            "session_id" => $data->sessionId ?? null,
            "user_id" => $data->userId ?? null,
            "role" => $data->role ?? null
        ];
    }
}
