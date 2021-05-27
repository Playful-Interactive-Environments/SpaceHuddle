<?php

namespace PieLab\GAB\Models;

/**
 * Permission roles for the session.
 * @OA\Schema(
 *   description="permission roles for the session",
 *   type="string",
 *   enum={"MODERATOR", "FACILITATOR", "PARTICIPANT"},
 *   example="MODERATOR"
 * )
 */
class Role
{
    const MODERATOR = "moderator";
    const FACILITATOR = "facilitator";
    const PARTICIPANT = "participant";
    const UNKNOWN = "unknown";
    const PARTICIPANT_INACTIVE = "participant_inactive";
}
