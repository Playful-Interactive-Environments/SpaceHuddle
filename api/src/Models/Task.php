<?php

namespace PieLab\GAB\Models;

/**
 * @OA\Schema(description="Task description")
 */
class Task
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
     * @OA\Property()
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
     * @OA\Property(ref="#/components/schemas/StateTask")
     */
    public ?string $state;

    /**
     * Creates a new task.
     * @param array|null $data Task data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->taskType = strtoupper($data["task_type"] ?? null);
        $this->name = $data["name"] ?? null;
        $this->description = $data["description"] ?? null;
        $this->parameter = json_decode($data["parameter"] ?? null);
        $this->order = (int)$data["order"] ?? null;
        $this->state = strtoupper($data["state"] ?? null);
    }
}
