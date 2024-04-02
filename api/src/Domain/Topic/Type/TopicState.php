<?php

namespace App\Domain\Topic\Type;

/**
 * List of possible task states.
 * @OA\Schema(
 *   description="display status on the participant devices",
 *   type="string",
 *   enum={"WAIT", "ACTIVE", "READ_ONLY", "DONE"},
 *   example="ACTIVE"
 * )
 */
class TopicState
{
    public const ACTIVE = "active";
    public const INACTIVE = "inactive";
}
