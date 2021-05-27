<?php

namespace PieLab\GAB\Models;

/**
 * List of possible states.
 * @OA\Schema(
 *   description="display status on the client devices",
 *   type="string",
 *   enum={"WAIT", "ACTIVE", "READ_ONLY", "DONE"},
 *   example="ACTIVE"
 * )
 */
class StateTask
{
    public const WAIT = "wait";
    public const ACTIVE = "active";
    public const READ_ONLY = "read_only";
    public const DONE = "done";
}
