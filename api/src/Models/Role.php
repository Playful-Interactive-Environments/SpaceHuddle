<?php

namespace PieLab\GAB\Models;

/**
 * Permission roles for the session.
 * @OA\Schema(
 *   description="permission roles for the session",
 *   type="string",
 *   enum={"MODERATOR", "FACILITATOR", "PARTICIPANT", "UNKNOWN", "PARTICIPANT_INACTIVE"},
 *   example="MODERATOR"
 * )
 */
class Role
{
    public const MODERATOR = "moderator";
    public const FACILITATOR = "facilitator";
    public const PARTICIPANT = "participant";
    public const UNKNOWN = "unknown";
    public const PARTICIPANT_INACTIVE = "participant_inactive";
}
