<?php

namespace App\Domain\User\Type;

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
}
