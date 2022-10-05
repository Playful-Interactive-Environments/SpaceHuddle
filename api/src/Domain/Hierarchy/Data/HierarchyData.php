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
     * Time of image storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $imageTimestamp;

    /**
     * ID of the parent node.
     * @var string|null
     * @OA\Property()
     */
    public ?string $parentId;

    /**
     * Participant id of the creator.
     * @var string|null
     * @OA\Property()
     */
    public ?string $participantId;

    /**
     * Participant is the creator of the task.
     * @var bool|null
     * @OA\Property()
     */
    public ?bool $isOwn;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $reader = new ArrayReader($data);
        $this->timestamp = $reader->findString("timestamp");
        $this->imageTimestamp = $reader->findString("image_timestamp");
        $this->parentId = $reader->findString("parent_id");
        $this->participantId = $reader->findString("participant_id");
        $this->isOwn = false;
    }
}
