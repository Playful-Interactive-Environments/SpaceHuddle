<?php

namespace App\Domain\TaskParticipantIteration\Type;

/**
 * Participant iteration states for the task.
 * @OA\Schema(
 *   description="Participant iteration states for the task",
 *   type="string",
 *   enum={"WIN", "LOOS", "PARTICIPATED", "IN_PROGRESS"},
 *   example="IN_PROGRESS"
 * )
 */
class TaskParticipantIterationStateType
{
    /** @var string */
    public const WIN = "win";

    /** @var string */
    public const LOOS = "loos";

    /** @var string */
    public const PARTICIPATED = "participated";

    /** @var string */
    public const IN_PROGRESS = "in_progress";
}
