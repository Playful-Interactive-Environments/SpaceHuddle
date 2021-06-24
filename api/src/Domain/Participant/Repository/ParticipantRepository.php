<?php

namespace App\Domain\Participant\Repository;

use App\Domain\Base\Repository\EncryptTrait;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\KeyGeneratorTrait;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Participant\Data\ParticipantData;
use App\Domain\Participant\Type\AvatarSymbol;
use App\Domain\Participant\Type\ParticipantState;
use App\Factory\QueryFactory;

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
            ParticipantData::class
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
            $participant = $this->isRegistered($sessionId, $data->ip);
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
    }

    /**
     * Connect to session.
     * @param object $data The data to be inserted
     * @return ParticipantData|null The participant entity
     * @throws GenericException
     */
    public function reconnect(string $browserKey): ?ParticipantData
    {
        $result = $this->get(["browser_key" => $browserKey]);
        if (!is_object($result)) {
            throw new DomainException("Entity $this->getEntityName() not found");
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
