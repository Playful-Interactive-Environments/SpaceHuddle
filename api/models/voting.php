<?php
/**
 * @OA\Schema(description="basic voting description")
 */
class Voting {

    /**
     * id of the idea
     * @var string
     * @OA\Property(example="uuid")
     */
    public $idea_id;

    /**
     * rating of the idea
     * @var int
     * @OA\Property()
     */
    public $rating;

    /**
     * Weighting of the idea.
     * @var float
     * @OA\Property()
     */
    public $detail_rating;

    public function __construct(array $data = null)
    {
        $this->idea_id = isset($data['idea_id']) ? $data['idea_id'] : null;
        $this->rating = isset($data['rating']) ? (int)$data['rating'] : null;
        $this->detail_rating = isset($data['detail_rating']) ? (float)$data['detail_rating'] : null;
    }
}

?>
