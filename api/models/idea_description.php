<?php
/**
 * @OA\Schema()
 */
class IdeaDescription {

    /**
     * The idea id.
     * @var int
     * @OA\Property()
     */
    public $id;

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
        $this->description = isset($data['description']) ? $data['description'] : null;
        $this->keywords = isset($data['keywords']) ? $data['keywords'] : null;
        $this->image = isset($data['image']) ? $data['image'] : null;
        $this->link = isset($data['link']) ? $data['link'] : null;
    }
}

?>
