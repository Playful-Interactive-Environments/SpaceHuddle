<?php
/**
 * @OA\Schema()
 */
class Group {

    /**
     * The idea id.
     * @var int
     * @OA\Property()
     */
    public $id;

    /**
     * Time of idea storage.
     * @var Date
     * @OA\Property()
     */
    public $time_stamp;

    /**
     * Description of the idea.
     * @var string
     * @OA\Property()
     */
    public $description;

    /**
     * Short description or keywords that describe the idea.
     * @var string
     * @OA\Property()
     */
    public $keywords;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->time_stamp = isset($data['time_stamp']) ? $data['time_stamp'] : null;
        $this->description = isset($data['description']) ? $data['description'] : null;
        $this->keywords = isset($data['keywords']) ? $data['keywords'] : null;
    }
}

?>
