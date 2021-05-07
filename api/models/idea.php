<?php
/**
 * @OA\Schema(description="total idea description")
 */
class Idea {

    /**
     * The idea id.
     * @var int
     * @OA\Property()
     */
    public $id;

    /**
     * current status of the idea
     * @var string
     * @OA\Property(ref="#/components/schemas/State_Idea")
     */
    public $state;

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

    /**
     * Image that describes the idea.
     * @var string
     * @OA\Property(type="string", format="binary")
     */
    public $image;

    /**
     * Link to a resource that describes the idea.
     * @var string
     * @OA\Property()
     */
    public $link;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->state = isset($data['state']) ? $data['state'] : null;
        $this->time_stamp = isset($data['time_stamp']) ? $data['time_stamp'] : null;
        $this->description = isset($data['description']) ? $data['description'] : null;
        $this->keywords = isset($data['keywords']) ? $data['keywords'] : null;
        $this->image = isset($data['image']) ? $data['image'] : null;
        $this->link = isset($data['link']) ? $data['link'] : null;
    }
}

?>
