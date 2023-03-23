<?php

namespace App\Domain\TaskParticipantState\Type;

/**
 * Participant states for the task.
 * @OA\Schema(
 *   description="Participant states for the task",
 *   type="string",
 *   enum={"IN_PROGRESS", "FINISHED"},
 *   example="IN_PROGRESS"
 * )
 */
class TaskParticipantStateType
{
    /** @var string */
    public const IN_PROGRESS = "in_progress";

    /** @var string */
    public const FINISHED = "finished";
}
