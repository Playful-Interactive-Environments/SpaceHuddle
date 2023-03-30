<?php

namespace App\Domain\TaskParticipantIterationStep\Type;

/**
 * Participant iteration step states for the task.
 * @OA\Schema(
 *   description="Participant iteration step states for the task",
 *   type="string",
 *   enum={"CORRECT", "WRONG", "NEUTRAL"},
 *   example="NEUTRAL"
 * )
 */
class TaskParticipantIterationStepStateType
{
    /** @var string */
    public const CORRECT = "correct";

    /** @var string */
    public const WRONG = "wrong";

    /** @var string */
    public const NEUTRAL = "neutral";
}
