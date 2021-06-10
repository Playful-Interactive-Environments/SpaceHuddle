<?php

namespace App\Data;

use App\Domain\Participant\Type\ParticipantState;
use App\Factory\QueryFactory;
use Lcobucci\JWT\Token\DataSet;

/**
 * Data of the logged in user
 * @package App\Domain\Base\Data
 */
class AuthorisationData
{
    /**
     * The user or participant ID.
     * @var string|null
     */
    public ?string $id = null;

    /**
     * The login type (user oder participant).
     * @var string|null
     */
    public ?string $role = null;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     * @param DataSet $data The data
     */
    public function __construct(QueryFactory $queryFactory, ?DataSet $data = null)
    {
        if (isset($data)) {
            $userId = $data->get("userId");
            $participantId = $data->get("participantId");
            if (isset($userId)) {
                $this->id = $userId;
                $this->role = AuthorisationRoleType::USER;
            } elseif (isset($participantId)) {
                $this->id = $participantId;
                $this->role = AuthorisationRoleType::PARTICIPANT;
                $this->checkParticipantState($queryFactory);
            } else {
                $this->role = AuthorisationRoleType::NONE;
            }
        } else {
            $this->role = AuthorisationRoleType::NONE;
        }
    }

    /**
     * Check if the participant is active.
     * @param QueryFactory $queryFactory The query factory
     */
    private function checkParticipantState(QueryFactory $queryFactory): void
    {
        $query = $queryFactory->newSelect("participant");
        $query->select(["*"])
            ->andWhere(["id" => $this->id]);
        $statement = $query->execute();

        $itemCount = $statement->rowCount();
        if ($itemCount > 0) {
            $participant = $statement->fetch("assoc");
            if (strtoupper($participant["state"]) != strtoupper(ParticipantState::ACTIVE)) {
                $this->role = AuthorisationRoleType::PARTICIPANT_INACTIVE;
            }
        }
    }

    /**
     * Checks if the current entity is a participant or not.
     * @return bool Returns true if it is a participant, otherwise false.
     */
    public function isParticipant(): bool
    {
        return ($this->role === AuthorisationRoleType::PARTICIPANT);
    }

    /**
     * Checks if the current entity is a user or not.
     * @return bool Returns true if it is a user, otherwise false.
     */
    public function isUser(): bool
    {
        return ($this->role === AuthorisationRoleType::USER);
    }

    /**
     * Checks if the current entity is logged in or not.
     * @return bool Returns true if logged in, otherwise false.
     */
    public function isLoggedIn(): bool
    {
        return ($this->role !== AuthorisationRoleType::USER or $this->role !== AuthorisationRoleType::PARTICIPANT);
    }
}
