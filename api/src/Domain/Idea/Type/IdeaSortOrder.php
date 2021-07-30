<?php

namespace App\Domain\Idea\Type;

/**
 * List of possible idea sort options.
 * @OA\Schema(
 *   description="current sort order of the idea",
 *   type="string",
 *   enum={"TIMESTAMP", "ALPHABETICAL", "STATE", "PARTICIPANT"},
 *   example="TIMESTAMP"
 * )
 */
class IdeaSortOrder
{
    public const TIMESTAMP = "timestamp";
    public const ALPHABETICAL = "alphabetical";
    public const STATE = "state";
    public const PARTICIPANT = "participant";
}
