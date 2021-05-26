<?php

namespace PieLab\GAB\Models;

/**
 * @OA\Schema(description="Information needed to display a task in the client application.")
 */
class ParticipantTask {

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

    /**
     * topic of the task
     * @var object|null
     * @OA\Property(ref="#/components/schemas/Topic")
     */
    public ?object $topic;

    public function __construct(array $data = null)
    {
        $this->id = $data['id'] ?? null;
        $this->task_type = strtoupper($data['TaskType'] ?? null);
        $this->name = $data['name'] ?? null;
        $this->parameter = json_decode($data['parameter'] ?? null);
        $this->order = (int)$data['order'] ?? null;
        $this->state = strtoupper($data['state'] ?? null);
        $this->topic = new Topic($data);
    }
}

?>
