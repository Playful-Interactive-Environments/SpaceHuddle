<?php

namespace PieLab\GAB\Models;

/**
 * List of possible states.
 * @OA\Schema(
 *   description="current status of the participant",
 *   type="string",
 *   enum={"ACTIVE", "INACTIVE"},
 *   example="ACTIVE"
 * )
 */
class StateParticipant {
  const ACTIVE = "active";
  const INACTIVE = "inactive";
}

?>
