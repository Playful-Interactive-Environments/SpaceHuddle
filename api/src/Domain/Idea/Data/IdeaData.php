<?php

namespace App\Domain\Idea\Data;

use App\Domain\Participant\Data\AvatarData;
use Selective\ArrayReader\ArrayReader;

/**
 * Represents an idea.
 * @OA\Schema(description="total idea description")
 */
class IdeaData extends IdeaAbstract
{
    /**
     * Current status of the idea.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/IdeaState")
     */
    public ?string $state;

    /**
     * Time of idea storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $timestamp;

    /**
     * Idea count.
     * @var int|null
     * @OA\Property()
     */
    public ?int $count;

    /**
     * To visually distinguish in the front end, each participant is assigned its own avatar.
     * @OA\Property(ref="#/components/schemas/AvatarData")
     */
    public ?AvatarData $avatar;

    /**
     * Order group name.
     * @var string|null
     * @OA\Property()
     */
    public ?string $order;

    /**
     * Category name.
     * @var string|null
     * @OA\Property()
     */
    public ?string $category;

    /**
     * Category id.
     * @var string|null
     * @OA\Property()
     */
    public ?string $category_id;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $reader = new ArrayReader($data);
        $this->state = strtoupper($reader->findString("state"));
        $this->timestamp = $reader->findString("timestamp");
        $this->count = $reader->findInt("count");
        $this->category = $reader->findString("category");
        $this->category_id = $reader->findString("category_id");
        $this->avatar = new AvatarData($data);
    }
}
