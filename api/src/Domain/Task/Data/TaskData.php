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
     * The keywords of the task.
     * @var string|null
     * @OA\Property()
     */
    public ?string $keywords;

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
     * Planned topic order.
     * @var int|null
     * @OA\Property(example=1)
     */
    public ?int $topicOrder;

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
     * Is task active on participant?
     * @var bool|null
     * @OA\Property()
     */
    public ?bool $activeOnParticipant;

    /**
     * Control public screen and participant view synchronously.
     * @var bool|null
     * @OA\Property()
     */
    public ?bool $syncPublicParticipant;

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
     * Number of participants who have worked on this task.
     * @var int|null
     * @OA\Property()
     */
    public ?int $participantCount;

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
        $this->keywords = $reader->findString("keywords");
        $this->parameter = (object)json_decode($reader->findString("parameter"));
        $this->order = $reader->findInt("order");
        $this->topicOrder = $reader->findInt("topic_order");
        $this->state = strtoupper($reader->findString("state"));
        $expirationTime = $reader->findString("expiration_time");
        $this->participantCount = $reader->findInt("participant_count");

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

        $this->activeOnParticipant = $reader->findString("active_on_participant") !== null;
        $this->syncPublicParticipant = $reader->findString("synchro_task") !== null;

        $module_id = $reader->findString("module_id");
        if ($module_id) {
            $this->modules = [new ModuleData(["id" => $module_id])];
        }
    }
}
