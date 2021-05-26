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
class StateTask {
  const WAIT = "wait";
  const ACTIVE = "active";
  const READ_ONLY = "read_only";
  const DONE = "done";
}

?>
