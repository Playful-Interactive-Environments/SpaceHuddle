<?php

namespace App\Domain\Hierarchy\Data;

use App\Domain\Idea\Data\IdeaAbstract;
use Selective\ArrayReader\ArrayReader;

/**
 * Represents a category.
 * @OA\Schema(description="category description")
 */
class HierarchyData extends IdeaAbstract
{
    /**
     * Time of group storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $timestamp;

    /**
     * ID of the parent node.
     * @var string|null
     * @OA\Property()
     */
    public ?string $parentId;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $reader = new ArrayReader($data);
        $this->timestamp = $reader->findString("timestamp");
        $this->parentId = $reader->findString("parent_id");
    }
}
