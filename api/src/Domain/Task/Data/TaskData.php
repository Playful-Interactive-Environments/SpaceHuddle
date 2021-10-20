<?php

namespace App\Domain\Task\Data;

use App\Domain\Module\Data\ModuleData;
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
     * The topic id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $topicId;

    /**
     * The session id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $sessionId;

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
     * How long is the task active?
     * @var int|null
     * @OA\Property(example=-1)
     */
    public ?int $remainingTime;

    /**
     * List of connected modules.
     * @var array<ModuleData>
     * @OA\Property(
     *       type="array",
     *       @OA\Items(
     *         ref="#/components/schemas/ModuleData"
     *       )
     *     )
     */
    public array $modules;

    /**
     * Creates a new task.
     * @param array $data Task data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->topicId = $reader->findString("topic_id");
        $this->sessionId = $reader->findString("session_id");
        $this->taskType = strtoupper($reader->findString("task_type"));
        $this->name = $reader->findString("name");
        $this->description = $reader->findString("description");
        $this->parameter = (object)json_decode($reader->findString("parameter"));
        $this->order = $reader->findInt("order");
        $this->state = strtoupper($reader->findString("state"));
        $expirationTime = $reader->findString("expiration_time");

        if ($expirationTime) {
            $expirationTime = strtotime($expirationTime);
            if ($expirationTime) {
                $now = strtotime("now");
                $this->remainingTime = $expirationTime - $now;
                if ($this->remainingTime < 0) {
                    $this->remainingTime = 0;
                }
            }
        } else {
            $this->remainingTime = null;
        }

        $module_id = $reader->findString("module_id");
        if ($module_id) {
            $this->modules = [new ModuleData(["id" => $module_id])];
        }
    }
}
