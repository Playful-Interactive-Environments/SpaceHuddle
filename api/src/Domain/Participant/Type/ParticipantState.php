<?php

namespace App\Domain\Participant\Type;

/**
 * List of possible participant states.
 * @OA\Schema(
 *   description="current status of the participant",
 *   type="string",
 *   enum={"ACTIVE", "INACTIVE"},
 *   example="ACTIVE"
 * )
 */
class ParticipantState
{
    public const ACTIVE = "active";
    public const INACTIVE = "inactive";
}
