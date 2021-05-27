<?php

namespace PieLab\GAB\Models;

/**
 * Describes a voting result.
 * @OA\Schema(description="description of the aggregated voting result")
 */
class VotingResult
{
    /**
     * Evaluated idea.
     * @var IdeaAbstract|null
     * @OA\Property(ref="#/components/schemas/IdeaAbstract")
     */
    public ?IdeaAbstract $idea;

    /**
     * Sum of the idea rating.
     * @var int|null
     * @OA\Property()
     */
    public ?int $ratingSum;

    /**
     * Sum of the idea weighting.
     * @var float|null
     * @OA\Property()
     */
    public ?float $detailRatingSum;

    /**
     * Creates a new voting result.
     * @param array|null $data VotingResult data.
     */
    public function __construct(array $data = null)
    {
        $this->idea = new IdeaAbstract($data);
        $this->ratingSum = (int)$data["rating"] ?? null;
        $this->detailRatingSum = (float)$data["detail_rating"] ?? null;
    }
}
