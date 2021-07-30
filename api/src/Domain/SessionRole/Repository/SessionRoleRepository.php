<?php

namespace App\Domain\SessionRole\Repository;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\SessionRole\Data\SessionRoleData;
use App\Factory\QueryFactory;
use DomainException;

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

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return SessionRoleData|array<SessionRoleData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|SessionRoleData|array
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["session_role.*", "user.username"])
            ->innerJoin("user", "session_role.user_id = user.id")
            ->andWhere($conditions)
            ->order($sortConditions);

        return $this->fetchAll($query);
    }

    /**
     * Get entity by ID.
     * @param string $id The entity ID.
     * @return SessionRoleData|null The result entity.
     * @throws GenericException
     */
    public function getById(string $id): ?SessionRoleData
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isUser()) {
            $result = $this->get([
                "session_role.session_id" => $id,
                "session_role.user_id" =>  $authorisation->id
            ]);
            if (!is_object($result)) {
                throw new DomainException("Entity $this->entityName not found");
            }
            return $result;
        }
        return null;
    }

    /**
     * Get entity by session ID and username.
     * @param string $sessionId The session ID.
     * @param string $username The username.
     * @return SessionRoleData|null The result entity.
     * @throws GenericException
     */
    public function getByUsername(string $sessionId, string $username): ?SessionRoleData
    {
        $userId = $this->getUserId($username);
        $result = $this->get([
            "session_role.session_id" => $sessionId,
            "session_role.user_id" =>  $userId
        ]);
        return $result;
    }

    /**
     * Verifies that the username entered is the logged in user.
     * @param string $username Username to check.
     * @return string|null If true, the username entered is the logged in user.
     * @throws GenericException
     */
    public function isOwnUsername(string $username): ?string
    {
        $authorisation = $this->getAuthorisation();
        if ($authorisation->isUser()) {
            $query = $this->queryFactory->newSelect("user");
            $query->select(["id"])
                ->andWhere([
                    "username" => $username,
                    "id" => $authorisation->id
                ]);

            return ($query->execute()->rowCount() == 1);
        }
        return false;
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
     * @param object $data The session role data
     * @return object|null The new session
     * @throws GenericException
     */
    public function insert(object $data): ?object
    {
        $result = $this->getByUsername($data->sessionId, $data->username);
        if (is_null($result)) {
            $data->userId = $this->getUserId($data->username);

            $usedKeys = array_values($this->translateKeys((array)$data));
            $row = $this->formatDatabaseInput($data);
            $row = $this->unsetUnused($row, $usedKeys);

            $query = $this->queryFactory->newInsert($this->getEntityName(), $row);
            $query->execute();

            $result = $this->getByUsername($data->sessionId, $data->username);
        } else {
            $result = $this->update($data);
        }
        return $result;
    }

    /**
     * Update entity row.
     * @param object|array $data The entity to change.
     * @return object|null The result entity.
     * @throws GenericException
     */
    public function update(object|array $data): ?object
    {
        if (!is_array($data)) {
            $username = $data->username;
            $usedKeys = array_values($this->translateKeys((array)$data));
            $data = $this->formatDatabaseInput($data);
            $data = $this->unsetUnused($data, $usedKeys);
        } else {
            $username = $data["username"];
        }
        $data["user_id"] = $this->getUserId($username);

        $query = $this->queryFactory->newUpdate($this->getEntityName(), ["role" => $data["role"]])
            ->andWhere([
                "session_id" => $data["session_id"],
                "user_id" => $data["user_id"],
            ]);
        $query->execute();

        return $this->getByUsername($data["session_id"], $username);
    }

    /**
     * Delete entity row.
     * @param object $data The session role data
     * @return void
     * @throws GenericException
     */
    public function delete(object $data): void
    {
        $data->userId = $this->getUserId($data->username);
        $this->queryFactory->newDelete($this->getEntityName())
            ->andWhere([
                "session_id" => $data->sessionId,
                "user_id" => $data->userId
            ])
            ->execute();
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
