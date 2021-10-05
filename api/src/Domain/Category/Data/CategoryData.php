<?php

namespace App\Domain\Category\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a category.
 * @OA\Schema(description="category description")
 */
class CategoryData
{
    /**
     * The idea id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Time of group storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $timestamp;

    /**
     * Description of the group.
     * @var string|null
     * @OA\Property()
     */
    public ?string $description;

    /**
     * Short description or keywords that describe the group.
     * @var string|null
     * @OA\Property()
     */
    public ?string $keywords;

    /**
     * Image that describes the idea.
     * @var string|null
     * @OA\Property(type="string", format="binary")
     */
    public ?string $image;

    /**
     * Link to a resource that describes the idea.
     * @var string|null
     * @OA\Property()
     */
    public ?string $link;

    /**
     * Variable json parameters depending on the task type.
     * @var object|null
     * @OA\Property(type="object", format="json")
     */
    public ?object $parameter;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->description = $reader->findString("description");
        $this->keywords = $reader->findString("keywords");
        $this->timestamp = $reader->findString("timestamp");
        $this->image = $reader->findString("image");
        $this->link = $reader->findString("link");
        $this->parameter = (object)json_decode($reader->findString("parameter"));
    }
}
