<?php

namespace PieLab\GAB\Models;

/**
 * Represents a topic.
 * @OA\Schema(description="topic description")
 */
class Topic
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
     * @param array|null $data Task data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->title = $data["title"] ?? null;
        $this->description = $data["description"] ?? null;
        $this->activeTaskId = $data["active_task_id"] ?? null;
    }
}
