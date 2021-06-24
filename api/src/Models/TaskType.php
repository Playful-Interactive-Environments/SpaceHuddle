<?php

namespace PieLab\GAB\Models;

/**
 * Possible task types.
 * @OA\Schema(
 *   description="possible task types",
 *   type="string",
 *   enum={"INFORMATION", "BRAINSTORMING", "SELECTION", "CATEGORISATION", "VOTING"},
 *   example="BRAINSTORMING"
 * )
 */
class TaskType
{
    public const INFORMATION = "information";
    public const BRAINSTORMING = "brainstorming";
    public const SELECTION = "selection";
    public const CATEGORISATION = "categorisation";
    public const VOTING = "voting";
}
