<?php
/**
 * @OA\Schema()
 */
class VotingResult {

    /**
     * evaluated idea
     * @var string
     * @OA\Property(ref="#/components/schemas/IdeaDescription")
     */
    public $idea;

    /**
     * Sum of the idea rating
     * @var int
     * @OA\Property()
     */
    public $rating_sum;

    /**
     * Sum of the idea weighting
     * @var float
     * @OA\Property()
     */
    public $detail_rating_sum;

    public function __construct(array $data = null)
    {
        $this->idea = isset($data['idea']) ? $data['idea'] : null;
        $this->rating_sum = isset($data['rating']) ? $data['rating'] : null;
        $this->detail_rating_sum = isset($data['detail_rating']) ? $data['detail_rating'] : null;
    }
}

?>
