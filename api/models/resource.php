<?php
/**
 * @OA\Schema(description="resource description")
 */
class Resource {

    /**
     * The resource id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Title of the resource.
     * @var string|null
     * @OA\Property()
     */
    public ?string $title;
    /**
     * Image of the resource.
     * @var string|null
     * @OA\Property(type="string", format="binary")
     */
    public ?string $image;

    /**
     * Link to a resource.
     * @var string|null
     * @OA\Property()
     */
    public ?string $link;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->title = isset($data['title']) ? $data['title'] : null;
        $this->image = isset($data['image']) ? $data['image'] : null;
        $this->link = isset($data['link']) ? $data['link'] : null;
    }
}

?>
