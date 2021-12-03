<?php

namespace App\Domain\View\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a category.
 * @OA\Schema(description="category description")
 */
class ViewData
{
    /**
     * type of the view.
     * @var string|null
     * @OA\Property()
     */
    public ?string $type;

    /**
     * detail type of the view.
     * @var string|null
     * @OA\Property()
     */
    public ?string $detailType;

    /**
     * ID of the view.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Connected Task ID of the view.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $taskId;

    /**
     * name of the view.
     * @var string|null
     * @OA\Property()
     */
    public ?string $name;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->type = $reader->findString("type");
        $this->detailType = $reader->findString("detail_type");
        $this->id = $reader->findString("id");
        $this->taskId = $reader->findString("task_id");
        $this->name = $reader->findString("name");
    }
}
