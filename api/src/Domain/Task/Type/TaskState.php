<?php

namespace App\Domain\Task\Type;

/**
 * List of possible task states.
 * @OA\Schema(
 *   description="display status on the client devices",
 *   type="string",
 *   enum={"WAIT", "ACTIVE", "READ_ONLY", "DONE"},
 *   example="ACTIVE"
 * )
 */
class TaskState
{
    public const WAIT = "wait";
    public const ACTIVE = "active";
    public const READ_ONLY = "read_only";
    public const DONE = "done";
}
