<?php

namespace PieLab\GAB\Models;

/**
 * List of possible states.
 * @OA\Schema(
 *   description="current status of the module",
 *   type="string",
 *   enum={"ACTIVE", "INACTIVE"},
 *   example="ACTIVE"
 * )
 */
class StateModule {
  const ACTIVE = "active";
  const INACTIVE = "inactive";
}

?>
