<?php

namespace App\Domain\Idea\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a category.
 * @OA\Schema(description="category description")
 */
class CategoryInfoData
{
    /**
     * Category id.
     * @var string|null
     * @OA\Property()
     */
    public ?string $id;

    /**
     * Category name.
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
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("category_id");
        $this->name = $reader->findString("category");
        $this->parameter = (object)json_decode($reader->findString("category_parameter"));
    }

    public function toString() {
        return "$this->name";
    }
}
