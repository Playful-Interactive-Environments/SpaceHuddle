<?php

namespace App\Domain\Participant\Repository;

use App\Domain\Base\Repository\EncryptTrait;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\KeyGeneratorTrait;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Participant\Data\ParticipantData;
use App\Domain\Participant\Data\ParticipantTaskData;
use App\Domain\Participant\Type\AvatarSymbol;
use App\Domain\Participant\Type\ParticipantState;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Topic\Data\TopicData;
use App\Factory\QueryFactory;
use Cake\I18n\Time;
use DomainException;

/**
 * Repository
 */
class ParticipantRepository implements RepositoryInterface
{
    use KeyGeneratorTrait;
    use EncryptTrait;
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
            "participant",
            ParticipantData::class,
            "session_id",
            SessionRepository::class
        );
    }

    /**
     * Connect to session.
     * @param object $data The data to be inserted
     * @return ParticipantData|null The new created entity
     * @throws GenericException
     */
    public function connect(object $data): ?ParticipantData
    {
        $query = $this->queryFactory->newSelect("session");
        $query->select(["id"])
            ->andWhere([
                "connection_key" => $data->sessionKey
            ]);
        $result = $query->execute()->fetch("assoc");
        if (is_array($result)) {
            $sessionId = $result["id"];
            if (!isset($data->ip)) {
                $data->ip = $this->getClientIp();
            }
            //todo: set unique client ip -> currently not working, several users get the same participant id
            //$participant = $this->isRegistered($sessionId, $data->ip);
            $participant = null;
            if (is_null($participant)) {
                $data->sessionId = $sessionId;
                $data->ipHash = self::encryptText($data->ip);
                $data->browserKey = $this->generateNewConnectionKey("browser_key", $data->sessionKey . ".");
                $data->color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                $data->symbol = AvatarSymbol::getRandomValue();
                $data->state = ParticipantState::ACTIVE;

                $participant = $this->insert($data);
            }
            return $participant;
        }
        return null;
    }

    /**
     * Determine participant IP address
     * @return string Client IP
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Reconnect to session.
     * @param string $browserKey Connection key generated individually for the participant.
     * @return ParticipantData|null The participant entity
     * @throws GenericException
     */
    public function reconnect(string $browserKey): ?ParticipantData
    {
        $result = $this->get(["browser_key" => $browserKey]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->entityName not found");
        }
        return $result;
    }

    /**
     * Checks whether the session key exists.
     * @param string $sessionKey The session key.
     * @return bool True if session key exists.
     */
    public function checkSessionKey(string $sessionKey): bool
    {
        $query = $this->queryFactory->newSelect("session");
        $query->select(["id"])
            ->andWhere([
                "connection_key" => $sessionKey
            ]);
        return ($query->execute()->rowCount() > 0);
    }

    /**
     * Checks whether the browser key exists.
     * @param string $browserKey The browser key.
     * @return bool True if browser key exists.
     */
    public function checkBrowserKey(string $browserKey): bool
    {
        $query = $this->queryFactory->newSelect("participant");
        $query->select(["id"])
            ->andWhere([
                "browser_key" => $browserKey
            ]);
        return ($query->execute()->rowCount() > 0);
    }

    /**
     * Checks if the session assigned to the session key is still valid.
     * @param string $sessionKey The session key.
     * @return bool True if session is valid.
     */
    public function checkExpirationDateForConnect(string $sessionKey): bool
    {
        $query = $this->queryFactory->newSelect("session");
        $query->select(["id"])
            ->andWhere([
                "connection_key" => $sessionKey,
                "expiration_date >= current_timestamp()"
            ]);
        return ($query->execute()->rowCount() > 0);
    }

    /**
     * Checks if the session assigned to the browser key is still valid.
     * @param string $browserKey The browser key.
     * @return bool True if session is valid.
     */
    public function checkExpirationDateForReconnect(string $browserKey): bool
    {
        $query = $this->queryFactory->newSelect("participant");
        $query->select(["participant.id"])
            ->innerJoin("session", "session.id = participant.session_id")
            ->andWhere([
                "participant.browser_key" => $browserKey,
                "session.expiration_date >= current_timestamp()"
            ]);
        return ($query->execute()->rowCount() > 0);
    }

    /**
     * Checks if a participant is registered.
     * @param string|null $sessionId The session's ID.
     * @param string|null $ip The participant's IP hash.
     * @return ParticipantData|null Returns an object if registered, otherwise null.
     */
    protected function isRegistered(?string $sessionId = null, ?string $ip = null): ?ParticipantData
    {
        $result = self::checkEncryptTextAndGetRow(["session_id" => $sessionId], $ip, "ip_hash");
        if (is_array($result)) {
            return new ParticipantData($result);
        }
        return null;
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $this->queryFactory->newUpdate("vote", ["participant_id" => null])
            ->andWhere(["participant_id" => $id])
            ->execute();

        $this->queryFactory->newUpdate("idea", ["participant_id" => null])
            ->andWhere(["participant_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("module_state")
            ->andWhere(["participant_id" => $id])
            ->execute();

        $this->queryFactory->newDelete("random_idea")
            ->andWhere(["participant_id" => $id])
            ->execute();
    }

    /**
     * List all tasks for the currently logged-in participant.
     * @param string $id The entity ID.
     * @return array The list of tasks in JSON format.
     */
    public function getTasks(string $id): array
    {
        $query = $this->queryFactory->newSelect("task");
        $query->select(["topic.*", "task.*"])
            ->innerJoin("topic", "topic.id = task.topic_id")
            ->innerJoin("session", "session.id = topic.session_id")
            ->innerJoin("participant", "participant.session_id = session.id")
            ->whereInList("task.state", [
                strtoupper(TaskState::ACTIVE),
                strtoupper(TaskState::READ_ONLY)
            ])
            ->andWhere([
                "participant.id" => $id,
                "session.expiration_date >= current_timestamp()",
                "(task.expiration_time IS NULL OR task.expiration_time >= current_timestamp())"
            ]);

        $result = $this->fetchAll($query, ParticipantTaskData::class);
        if (isset($result)) {
            if (is_array($result)) {
                return $result;
            }
            return [$result];
        }

        return [];
    }

    /**
     * List all topics for the current participant.
     * @param string $id The entity ID.
     * @return array A list of topics in JSON format.
     */
    public function getTopics(string $id): array
    {
        $query = $this->queryFactory->newSelect("topic");
        $query->select(["topic.*"])
            ->innerJoin("session", "session.id = topic.session_id")
            ->innerJoin("participant", "participant.session_id = session.id")
            ->andWhere([
                "participant.id" => $id,
                "session.expiration_date >= current_timestamp()"
            ]);

        $result = $this->fetchAll($query, TopicData::class);
        if (isset($result)) {
            if (is_array($result)) {
                return $result;
            }
            return [$result];
        }

        return [];
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
            "browser_key" => $data->browserKey ?? null,
            "state" => $data->state ?? null,
            "color" => $data->color ?? null,
            "symbol" => $data->symbol ?? null,
            "ip_hash" => $data->ipHash ?? null
        ];
    }
}
