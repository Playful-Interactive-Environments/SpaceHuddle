<?php


namespace App\Domain\Idea\Type;

/**
 * List of possible idea states.
 * @OA\Schema(
 *   description="current status of the idea",
 *   type="string",
 *   enum={"NEW", "DUPLICATE", "THUMBS_DOWN", "THUMBS_UP", "HANDLED"},
 *   example="NEW"
 * )
 */
class IdeaState
{
    public const NEW = "new";
    public const DUPLICATE = "duplicate";
    public const HANDLED = "handled";
    public const THUMBS_DOWN = "thumbs_down";
    public const THUMBS_UP = "thumbs_up";
}
