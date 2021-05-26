<?php
/**
 * @OA\Schema(description="Task description")
 */
class Task {

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
    public ?string $task_type;

    /**
     * The name of the task.
     * @var string|null
     * @OA\Property()
     */
    public ?string $name;

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
     * current status of the task
     * @var string|null
     * @OA\Property(ref="#/components/schemas/StateTask")
     */
    public ?string $state;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->task_type = strtoupper(isset($data['task_type']) ? $data['task_type'] : null);
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->parameter = json_decode(isset($data['parameter']) ? $data['parameter'] : null);
        $this->order = isset($data['order']) ? (int)$data['order'] : null;
        $this->state = strtoupper(isset($data['state']) ? $data['state'] : null);
    }
}

?>
