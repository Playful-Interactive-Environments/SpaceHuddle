<?php

namespace App\Domain\Topic\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a topic.
 * @OA\Schema(description="topic description")
 */
class TopicData
{
    /**
     * The topic id.
     * @var string|null
     * @OA\Property()
     */
    public ?string $id;

    /**
     * The topic title.
     * @var string|null
     * @OA\Property()
     */
    public ?string $title;

    /**
     * The topic description.
     * @var string|null
     * @OA\Property()
     */
    public ?string $description;

    /**
     * Data of the active topic task.
     * @var string|null
     * @OA\Property(example=null)
     */
    public ?string $activeTaskId;

    /**
     * Creates a new task.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->title = $reader->findString("title");
        $this->description = $reader->findString("description");
        $this->activeTaskId = $reader->findString("active_task_id");
    }
}
