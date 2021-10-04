<?php


namespace App\Domain\Idea\Type;

/**
 * List of possible idea states.
 * @OA\Schema(
 *   description="current status of the idea",
 *   type="string",
 *   enum={"NEW", "DUPLICATE", "INAPPROPRIATE", "HANDLED"},
 *   example="NEW"
 * )
 */
class IdeaState
{
    public const NEW = "new";
    public const DUPLICATE = "duplicate";
    public const INAPPROPRIATE = "inappropriate";
    public const HANDLED = "handled";
}
