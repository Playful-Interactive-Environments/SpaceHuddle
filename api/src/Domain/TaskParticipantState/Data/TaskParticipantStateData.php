<?php

namespace App\Domain\TaskParticipantState\Data;

use App\Domain\Participant\Data\AvatarData;
use Selective\ArrayReader\ArrayReader;

/**
 * Describes a user role for a session.
 * @OA\Schema(description="description of the user role for the session")
 */
class TaskParticipantStateData
{
    /**
     * The entity ID.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id = null;

    /**
     * The task id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $taskId;

    /**
     * How many times the task was called by the participant.
     * @var int
     * @OA\Property()
     */
    public int $count;

    /**
     * Participant task state.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/TaskParticipantStateType")
     */
    public ?string $state;

    /**
     * Variable json parameters depending on the task type.
     * @var object|null
     * @OA\Property(type="object", format="json")
     */
    public ?object $parameter;

    /**
     * To visually distinguish in the front end, each participant is assigned its own avatar.
     * @OA\Property(ref="#/components/schemas/AvatarData")
     */
    public ?AvatarData $avatar;

    /**
     * How many times the task was called by the participant.
     * @var int
     * @OA\Property()
     */
    public int $iteration_count;

    /**
     * How many times the task was called by the participant.
     * @var int
     * @OA\Property()
     */
    public int $iteration_done_count;

    /**
     * Creates a new user role for a session.
     * @param array $data Selection data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->taskId = $reader->findString("task_id");
        $this->count = $reader->findInt("count");
        $this->state = strtoupper($reader->findString("state"));
        $this->parameter = (object)json_decode($reader->findString("parameter"));
        $this->avatar = new AvatarData($data);
        $this->iteration_count = $reader->findInt("iteration_count");
        $this->iteration_done_count = $reader->findInt("iteration_done_count");
    }
}
