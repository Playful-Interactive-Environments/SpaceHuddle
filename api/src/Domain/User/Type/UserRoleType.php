<?php

namespace App\Domain\User\Type;

use App\Data\AuthorisationRoleType;

/**
 * Permission roles for the session.
 * @OA\Schema(
 *   description="Permission roles for the session",
 *   type="string",
 *   enum={"MODERATOR", "FACILITATOR", "PARTICIPANT", "UNKNOWN", "PARTICIPANT_INACTIVE"},
 *   example="MODERATOR"
 * )
 */
class UserRoleType
{
    /** @var string */
    public const MODERATOR = "moderator";

    /** @var string */
    public const FACILITATOR = "facilitator";

    /** @var string */
    public const PARTICIPANT = "participant";

    /** @var string */
    public const UNKNOWN = "unknown";

    /** @var string */
    public const PARTICIPANT_INACTIVE = "participant_inactive";

    /**
     * Map a user role based on the given token to the default role permission for the given session.
     * @param string $authorisationRoleType User role based on the given token.
     * @return string Default permission roles for the session.
     */
    public static function mapAuthorisationRoleType(string $authorisationRoleType): string
    {
        if ($authorisationRoleType === AuthorisationRoleType::USER) {
            return self::MODERATOR;
        }
        if ($authorisationRoleType === AuthorisationRoleType::PARTICIPANT) {
            return self::PARTICIPANT;
        }
        if ($authorisationRoleType === AuthorisationRoleType::PARTICIPANT_INACTIVE) {
            return self::PARTICIPANT_INACTIVE;
        }
        if ($authorisationRoleType === AuthorisationRoleType::NONE) {
            return self::UNKNOWN;
        }
        return self::UNKNOWN;
    }
}
