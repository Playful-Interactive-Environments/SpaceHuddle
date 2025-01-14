<?php

namespace App\Data;

use App\Domain\User\Type\UserRoleType;
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
    public ?string $type = null;

    /**
     * The login role (user oder admin).
     * @var string|null
     */
    public ?string $role = null;

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     * @param DataSet $data The data
     */
    public function __construct(?DataSet $data = null)
    {
        $this->role = UserRoleType::USER;
        if (isset($data) && $data->get("action") == "login") {
            $userId = $data->get("userId");
            $participantId = $data->get("participantId");

            if (isset($userId)) {
                $this->id = $userId;
                $this->type = AuthorisationType::USER;
                $this->role = $data->get("role");
            } elseif (isset($participantId)) {
                $this->id = $participantId;
                $this->type = AuthorisationType::PARTICIPANT;
            } else {
                $this->type = AuthorisationType::NONE;
            }
        } else {
            $this->type = AuthorisationType::NONE;
        }
    }

    public static function instanceFromUserId(string $userId): AuthorisationData {
        $instance = new self();
        $instance->id = $userId;
        $instance->type = AuthorisationType::USER;
        return $instance;
    }

    /**
     * Checks if the current entity is a participant or not.
     * @return bool Returns true if it is a participant, otherwise false.
     */
    public function isParticipant(): bool
    {
        return ($this->type === AuthorisationType::PARTICIPANT);
    }

    /**
     * Checks if the current entity is a user or not.
     * @return bool Returns true if it is a user, otherwise false.
     */
    public function isUser(): bool
    {
        return ($this->type === AuthorisationType::USER);
    }

    /**
     * Checks if the current entity is logged in or not.
     * @return bool Returns true if logged in, otherwise false.
     */
    public function isLoggedIn(): bool
    {
        return ($this->type === AuthorisationType::USER or $this->type === AuthorisationType::PARTICIPANT);
    }
}
