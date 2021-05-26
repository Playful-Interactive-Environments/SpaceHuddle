<?php
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

/**
 * List of possible states.
 * @OA\Schema(
 *   description="current status of the idea",
 *   type="string",
 *   enum={"NEW", "DUPPLIKAT", "INAPPROPRIATE", "HANDLED"},
 *   example="NEW"
 * )
 */
class StateIdea {
  const NEW = "new";
  const DUPLICATE = "duplicate";
  const INAPPROPRIATE = "inappropriate";
  const HANDLED = "handled";
}

?>
