<?php

namespace App\Domain\Module\Type;

/**
 * List of possible module states.
 * @OA\Schema(
 *   description="current status of the module",
 *   type="string",
 *   enum={"ACTIVE", "INACTIVE"},
 *   example="ACTIVE"
 * )
 */
class ModuleState
{
    const ACTIVE = "active";
    const INACTIVE = "inactive";
}
