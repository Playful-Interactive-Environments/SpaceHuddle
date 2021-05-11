<?php
require_once('idea_abstract.php');
/**
 * @OA\Schema(description="description of the aggregated voting result")
 */
class VotingResult {

    /**
     * evaluated idea
     * @var string
     * @OA\Property(ref="#/components/schemas/IdeaAbstract")
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
        $this->idea = new IdeaAbstract($data);
        $this->rating_sum = isset($data['rating']) ? (int)$data['rating'] : null;
        $this->detail_rating_sum = isset($data['detail_rating']) ? (float)$data['detail_rating'] : null;
    }
}

?>
