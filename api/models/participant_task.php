<?php
require_once('topic.php');
/**
 * @OA\Schema(description="Information needed to display a task in the client application.")
 */
class ParticipantTask {

    /**
     * The task id.
     * @var int
     * @OA\Property()
     */
    public $id;

    /**
     * The type of the task.
     * @var string
     * @OA\Property()
     */
    public $task_type;

    /**
     * The name of the task.
     * @var string
     * @OA\Property()
     */
    public $name;

    /**
     * Variable json parameters depending on the task type.
     * @var object
     * @OA\Property(type="object", format="json")
     */
    public $parameter;

    /**
     * Planned task order.
     * @var int
     * @OA\Property(example=1)
     */
    public $order;

    /**
     * current status of the task
     * @var string
     * @OA\Property(ref="#/components/schemas/State_Task")
     */
    public $state;

    /**
     * topic of the task
     * @var object
     * @OA\Property(ref="#/components/schemas/Topic")
     */
    public $topic;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->task_type = strtoupper(isset($data['task_type']) ? $data['task_type'] : null);
        $this->name = isset($data['name']) ? $data['name'] : null;
        $this->parameter = json_decode(isset($data['parameter']) ? $data['parameter'] : null);
        $this->order = isset($data['order']) ? (int)$data['order'] : null;
        $this->state = strtoupper(isset($data['state']) ? $data['state'] : null);
        $this->topic = new Topic($data);
    }
}

?>
