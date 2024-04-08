<?php

namespace App\Domain\View\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a view.
 * @OA\Schema(description="view description")
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
     * name of the topic.
     * @var string|null
     * @OA\Property()
     */
    public ?string $topicName;

    /**
     * ID of the topic.
     * @var string|null
     * @OA\Property()
     */
    public ?string $topicId;

    /**
     * name of the view.
     * @var array<string>
     * @OA\Property(
     *       type="array",
     *       @OA\Items(
     *         type="string"
     *       )
     *     )
     */
    public array $modules;

    /**
     * Creates a new view.
     * @param array $data View data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->type = $reader->findString("type");
        $this->detailType = $reader->findString("detail_type");
        $this->id = $reader->findString("id");
        $this->taskId = $reader->findString("task_id");
        $this->topicId = $reader->findString("topic_id");
        $this->topicName = $reader->findString("topic_name");
        $this->name = $reader->findString("name");
        $this->modules = preg_split("/,/", (string)$reader->findString("modules"));
    }
}
