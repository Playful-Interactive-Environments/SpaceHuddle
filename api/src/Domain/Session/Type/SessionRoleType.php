<?php

namespace App\Domain\Session\Type;

use App\Data\AuthorisationType;

/**
 * Permission roles for the session.
 * @OA\Schema(
 *   description="Permission roles for the session",
 *   type="string",
 *   enum={"MODERATOR", "FACILITATOR", "PARTICIPANT", "UNKNOWN", "PARTICIPANT_INACTIVE"},
 *   example="MODERATOR"
 * )
 */
class SessionRoleType
{
    /** @var string */
    public const MODERATOR = "moderator";

    /** @var string */
    public const FACILITATOR = "facilitator";

    /** @var string */
    public const PARTICIPANT = "participant";

    /** @var string */
    public const PARTICIPANT_INACTIVE = "participant_inactive";

    /** @var string */
    public const UNKNOWN = "unknown";

    /**
     * Map a user role based on the given token to the default role permission for the given session.
     * @param string $authorisationRoleType User role based on the given token.
     * @return string Default permission roles for the session.
     */
    public static function mapAuthorisationType(string $authorisationRoleType): string
    {
        if ($authorisationRoleType === AuthorisationType::USER) {
            return strtoupper(self::MODERATOR);
        }
        if ($authorisationRoleType === AuthorisationType::PARTICIPANT) {
            return strtoupper(self::PARTICIPANT);
        }
        if ($authorisationRoleType === AuthorisationType::NONE) {
            return strtoupper(self::UNKNOWN);
        }
        return strtoupper(self::UNKNOWN);
    }
}
