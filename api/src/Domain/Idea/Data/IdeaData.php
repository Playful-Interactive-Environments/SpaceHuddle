<?php

namespace App\Domain\Idea\Data;

use App\Domain\Participant\Data\AvatarData;
use phpDocumentor\Reflection\Types\Boolean;
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
    public ?string $orderGroup;

    /**
     * Category.
     * @OA\Property(ref="#/components/schemas/CategoryInfoData")
     */
    public ?CategoryInfoData $category;

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
        $this->state = strtoupper($reader->findString("state"));
        $this->timestamp = $reader->findString("timestamp");
        $this->count = $reader->findInt("count");
        $this->participantId = $reader->findString("participant_id");
        $this->isOwn = false;
        if ($reader->findString("category_id")) {
            $this->category = new CategoryInfoData($data);
        }
        $this->avatar = new AvatarData($data);
    }
}
