<?php

namespace PieLab\GAB\Models;

/**
 * Represents a category.
 * @OA\Schema(schema="Category", description="category description")
 */
class Group
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
     * Create a new Group.
     * @param array|null $data The group data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->timestamp = $data["timestamp"] ?? null;
        $this->description = $data["description"] ?? null;
        $this->keywords = $data["keywords"] ?? null;
    }
}