<?php
/**
 * List of possible states.
 * @OA\Schema(
 *   type="string",
 *   enum={"WAIT", "ACTIVE", "READ_ONLY", "DONE"},
 *   example="ACTIVE"
 * )
 */
class State_Task {
  const WAIT = "wait";
  const ACTIVE = "active";
  const READ_ONLY = "read_only";
  const DONE = "done";
}

/**
 * List of possible states.
 * @OA\Schema(
 *   type="string",
 *   enum={"NEW", "DUPPLIKAT", "INAPPROPRIATE", "HANDLED"},
 *   example="NEW"
 * )
 */
class State_Idea {
  const NEW = "new";
  const DUPPLIKAT = "dupplikate";
  const INAPPROPRIATE = "inappropriate";
  const HANDLED = "handled";
}

?>
