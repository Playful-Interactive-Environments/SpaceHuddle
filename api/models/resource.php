<?php
/**
 * @OA\Schema(description="resource description")
 */
class Resource {

    /**
     * The resource id.
     * @var int
     * @OA\Property()
     */
    public $id;

    /**
     * Title of the resource.
     * @var string
     * @OA\Property()
     */
    public $title;
    /**
     * Image of the resource.
     * @var string
     * @OA\Property(type="string", format="binary")
     */
    public $image;

    /**
     * Link to a resource.
     * @var string
     * @OA\Property()
     */
    public $link;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->title = isset($data['title']) ? $data['title'] : null;
        $this->image = isset($data['image']) ? $data['image'] : null;
        $this->link = isset($data['link']) ? $data['link'] : null;
    }
}

?>
