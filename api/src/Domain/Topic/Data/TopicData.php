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
     * The session id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $sessionId;

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
     * Planned task order.
     * @var int|null
     * @OA\Property(example=1)
     */
    public ?int $order;

    /**
     * Current status of the topic.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/TopicState")
     */
    public ?string $state;

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
        $this->sessionId = $reader->findString("session_id");
        $this->title = $reader->findString("title");
        $this->description = $reader->findString("description");
        $this->order = $reader->findInt("order");
        $this->state = strtoupper($reader->findString("state"));
        $this->activeTaskId = $reader->findString("active_task_id");
    }
}
