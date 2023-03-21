<?php

namespace App\Domain\Vote\Data;

use App\Domain\Idea\Data\IdeaAbstract;
use Selective\ArrayReader\ArrayReader;

/**
 * Describes a voting result.
 * @OA\Schema(description="description of the aggregated voting result")
 */
class VoteResultDetailData
{
    /**
     * Evaluated idea.
     * @var IdeaAbstract|null
     * @OA\Property(ref="#/components/schemas/IdeaAbstract")
     */
    public ?IdeaAbstract $idea;

    /**
     * the idea rating.
     * @var int|null
     * @OA\Property()
     */
    public ?int $rating;

    /**
     * the idea weighting.
     * @var float|null
     * @OA\Property()
     */
    public ?float $detailRating;

    /**
     * Number of participants who have cast a vote.
     * @var int|null
     * @OA\Property()
     */
    public ?int $countParticipant;

    /**
     * Creates a new vote result.
     * @param array $data Vote result data.
     */
    public function __construct(array $data = [])
    {
        $this->idea = new IdeaAbstract($data);

        $reader = new ArrayReader($data);
        $this->rating = $reader->findInt("rating");
        $this->detailRating = $reader->findFloat("detail_rating");
        $this->countParticipant = $reader->findInt("count_participant");
    }
}
