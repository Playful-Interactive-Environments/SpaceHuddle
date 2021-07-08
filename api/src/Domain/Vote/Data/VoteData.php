<?php

namespace App\Domain\Vote\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a voting.
 * @OA\Schema(description="basic voting description")
 */
class VoteData
{
    /**
     * ID of the voting.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * ID of the idea.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $ideaId;

    /**
     * Rating of the idea.
     * @var int|null
     * @OA\Property()
     */
    public ?int $rating;

    /**
     * Weighting of the idea.
     * @var float|null
     * @OA\Property()
     */
    public ?float $detailRating;

    /**
     * Creates a new vote.
     * @param array $data Vote data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->ideaId = $reader->findString("idea_id");
        $this->rating = $reader->findInt("rating");
        $this->detailRating = $reader->findFloat("detail_rating");
    }
}
