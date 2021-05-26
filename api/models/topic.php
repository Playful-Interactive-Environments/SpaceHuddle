<?php
/**
 * @OA\Schema(description="topic description")
 */
class Topic {

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
    public ?string $active_task_id;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->title = isset($data['title']) ? $data['title'] : null;
        $this->description = isset($data['description']) ? $data['description'] : null;
        $this->active_task_id = isset($data['active_task_id']) ? $data['active_task_id'] : null;
    }
}

?>
