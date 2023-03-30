<?php

namespace App\Domain\TaskParticipantIteration\Data;

use App\Domain\Participant\Data\AvatarData;
use Selective\ArrayReader\ArrayReader;

/**
 * Describes a user role for a session.
 * @OA\Schema(description="description of the user role for the session")
 */
class TaskParticipantIterationData
{
    /**
     * The entity ID.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id = null;

    /**
     * How many times the task was called by the participant.
     * @var int
     * @OA\Property()
     */
    public int $iteration;

    /**
     * Participant task state.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/TaskParticipantIterationStateType")
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
     * Creates a new user role for a session.
     * @param array $data Selection data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->iteration = $reader->findInt("iteration");
        $this->state = strtoupper($reader->findString("state"));
        $this->parameter = (object)json_decode($reader->findString("parameter"));
        $this->avatar = new AvatarData($data);
    }
}
