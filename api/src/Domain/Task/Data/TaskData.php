<?php

namespace App\Domain\Task\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a task.
 * @OA\Schema(description="task description")
 */
class TaskData
{
    /**
     * The task id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * The type of the task.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/TaskType")
     */
    public ?string $taskType;

    /**
     * The name of the task.
     * @var string|null
     * @OA\Property()
     */
    public ?string $name;

    /**
     * The description of the task.
     * @var string|null
     * @OA\Property()
     */
    public ?string $description;

    /**
     * Variable json parameters depending on the task type.
     * @var object|null
     * @OA\Property(type="object", format="json")
     */
    public ?object $parameter;

    /**
     * Planned task order.
     * @var int|null
     * @OA\Property(example=1)
     */
    public ?int $order;

    /**
     * Current status of the task.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/TaskState")
     */
    public ?string $state;

    /**
     * Creates a new task.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->taskType = strtoupper($reader->findString("task_type"));
        $this->name = $reader->findString("name");
        $this->description = $reader->findString("description");
        $this->parameter = (object)json_decode($reader->findString("parameter"));
        $this->order = $reader->findInt("order");
        $this->state = strtoupper($reader->findString("state"));
    }
}
