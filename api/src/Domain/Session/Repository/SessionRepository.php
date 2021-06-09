<?php

namespace App\Domain\Session\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Repository\AbstractRepository;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Type\UserRoleType;
use App\Factory\QueryFactory;
use App\Domain\Session\Data\SessionData;

/**
 * Repository
 */
class SessionRepository extends AbstractRepository
{
    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        parent::__construct(
            $queryFactory,
            "session",
            SessionData::class,
            "user_id",
            UserRepository::class);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        if ($authorisation->isUser()) {
            if (is_null($id)) {
                return strtoupper(UserRoleType::MODERATOR);
            }
            $query = $this->queryFactory->newSelect("session_role");
            $query->select(["*"])
                ->andWhere([
                    "session_id" => $id,
                    "user_id" => $authorisation->id
                ]);

            $statement = $query->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $result = $statement->fetch("assoc");
                return strtoupper($result["role"]);
            }
        } elseif ($authorisation->isParticipant()) {
            $query = $this->queryFactory->newSelect("participant");
            $query->select(["*"])
                ->andWhere([
                    "session_id" => $id,
                    "id" => $authorisation->id
                ]);
            if ($query->execute()->rowCount() > 0) {
                return strtoupper(UserRoleType::PARTICIPANT);
            }
        }
        return null;
    }

    /**
     * Insert session row.
     * @param object $data The session data
     * @return AbstractData|null The new session
     */
    public function insert(object $data): ?AbstractData
    {
        $data->connectionKey = $this->generateNewConnectionKey("connection_key");
        return parent::insert($data);
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @return AbstractData|array<AbstractData>|null The result entity(s).
     */
    public function get(array $conditions = []): null|AbstractData|array
    {
        if ($this->genericTableParameterSet()) {
            $query = $this->queryFactory->newSelect($this->entityName);
            $query->select(["*"])
                ->innerJoin("session_role", "session_role.session_id = session.id")
                ->andWhere($conditions);

            $rows = $query->execute()->fetchAll("assoc");
            if (is_array($rows) and sizeof($rows) > 0) {
                if (sizeof($rows) === 1) {
                    return new $this->resultClass($rows[0]);
                } else {
                    $result = [];
                    foreach ($rows as $resultItem) {
                        array_push($result, new $this->resultClass($resultItem));
                    }
                    return $result;
                }
            }
        }
        return null;
    }

    /**
     * Get entity by ID.
     * @param string $parentId The entity parent ID.
     * @return array<AbstractData> The result entity list.
     */
    public function getAll(string $parentId): array
    {
        if ($this->allGenericParameterSet()) {
            $result = $this->get(["session_role.user_id" => $parentId]);
            if (is_array($result)) {
                return $result;
            } elseif (isset($result)) {
                return [$result];
            }
        }
        return [];
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        if (is_object($parameter)) {
            $this->queryFactory->newInsert("session_role", [
                "session_id" => $id,
                "user_id" => $parameter->userId,
                "role" => strtoupper(UserRoleType::MODERATOR)
            ])->execute();
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $query = $this->queryFactory->newSelect("participant");
        $query->select(["id"]);
        $query->andWhere(["session_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            #$participant = new ParticipantRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $participantId = $resultItem["id"];
                #$participant->deleteById($participantId);
            }
        }

        $query = $this->queryFactory->newSelect("topic");
        $query->select(["id"]);
        $query->andWhere(["session_id" => $id]);

        $result = $query->execute()->fetchAll("assoc");
        if (is_array($result)) {
            //TODO: Implement an equivalent for getInstance
            #$topic = new TopicRepository($this->queryFactory);
            foreach ($result as $resultItem) {
                $topicId = $resultItem["id"];
                #$topic->deleteById($topicId);
            }
        }

        $this->queryFactory->newDelete("resource")
            ->andWhere(["session_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("session_role")
            ->andWhere(["session_id" => $id])
            ->execute();
    }

    /**
     * Convert to array.
     * @param object $data The entity data
     * @return array<string, mixed> The array
     */
    protected function toRow(object $data): array
    {
        return [
            "id" => $data->id ?? null,
            "title" => $data->title ?? null,
            "connection_key" => $data->connectionKey ?? null,
            "max_participants" => $data->maxParticipants ?? null,
            "expiration_date" => $data->expirationDate ?? null,
            "public_screen_module_id" => $data->publicScreenModuleId ?? null
        ];
    }
}
