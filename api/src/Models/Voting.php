<?php

namespace PieLab\GAB\Models;

/**
 * Represents a voting.
 * @OA\Schema(description="basic voting description")
 */
class Voting
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
     * Creates a new voting.
     * @param array|null $data Voting data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->ideaId = $data["idea_id"] ?? null;
        $this->rating = (int)$data["rating"] ?? null;
        $this->detailRating = (float)$data["detail_rating"] ?? null;
    }
}
