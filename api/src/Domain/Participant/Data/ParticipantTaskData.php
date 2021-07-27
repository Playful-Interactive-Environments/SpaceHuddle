<?php

namespace App\Domain\Participant\Data;

use App\Domain\Task\Data\TaskData;
use App\Domain\Topic\Data\TopicData;

/**
 * Information needed to display a task in the participant application.
 * @OA\Schema(description="Information needed to display a task in the participant application.")
 */
class ParticipantTaskData extends TaskData
{
    /**
     * Topic of the task.
     * @var object|null
     * @OA\Property(ref="#/components/schemas/TopicData")
     */
    public ?object $topic;

    /**
     * Creates a new task.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $data["id"] = $data["topic_id"];
        $this->topic = new TopicData($data);
    }
}
