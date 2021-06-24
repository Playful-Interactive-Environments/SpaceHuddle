<?php

namespace App\Domain\Task\Type;

/**
 * Possible task types.
 * @OA\Schema(
 *   description="possible task types",
 *   type="string",
 *   enum={"INFORMATION", "BRAINSTORMING", "SELECTION", "GROUPING", "VOTING"},
 *   example="BRAINSTORMING"
 * )
 */
class TaskType
{
    public const INFORMATION = "information";
    public const BRAINSTORMING = "brainstorming";
    public const SELECTION = "selection";
    public const GROUPING = "grouping";
    public const VOTING = "voting";
}
