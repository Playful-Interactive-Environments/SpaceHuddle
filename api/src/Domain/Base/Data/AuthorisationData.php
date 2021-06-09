<?php

namespace App\Domain\Base\Data;

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
     * @param DataSet $data The data
     */
    public function __construct(?DataSet $data = null)
    {
        if (isset($data)) {
            $userId = $data->get("userId");
            $participantId = $data->get("participantId");
            if (isset($userId)) {
                $this->id = $userId;
                $this->role = AuthorisationRole::USER;
            } elseif (isset($participantId)) {
                $this->id = $participantId;
                $this->role = AuthorisationRole::PARTICIPANT;
            } else {
                $this->role = AuthorisationRole::NONE;
            }
        } else {
            $this->role = AuthorisationRole::NONE;
        }
    }

    /**
     * Checks if the current entity is a participant or not.
     * @return bool Returns true if it is a participant, otherwise false.
     */
    public function isParticipant(): bool
    {
        return ($this->role === AuthorisationRole::PARTICIPANT);
    }

    /**
     * Checks if the current entity is a user or not.
     * @return bool Returns true if it is a user, otherwise false.
     */
    public function isUser(): bool
    {
        return ($this->role === AuthorisationRole::USER);
    }

    /**
     * Checks if the current entity is logged in or not.
     * @return bool Returns true if logged in, otherwise false.
     */
    public function isLoggedIn(): bool
    {
        return ($this->role !== AuthorisationRole::USER and $this->role !== AuthorisationRole::PARTICIPANT);
    }
}
