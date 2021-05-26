<?php

namespace PieLab\GAB\Models;

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
