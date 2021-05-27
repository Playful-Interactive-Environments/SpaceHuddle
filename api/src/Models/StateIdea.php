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
class StateIdea
{
    public const NEW = "new";
    public const DUPLICATE = "duplicate";
    public const INAPPROPRIATE = "inappropriate";
    public const HANDLED = "handled";
}
