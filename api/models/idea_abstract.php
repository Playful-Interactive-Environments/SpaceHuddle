<?php
/**
 * @OA\Schema(description="reduced idea description for voting result")
 */
class IdeaAbstract {

    /**
     * The idea id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Description of the idea.
     * @var string|null
     * @OA\Property()
     */
    public ?string $description;

    /**
     * Short description or keywords that describe the idea.
     * @var string|null
     * @OA\Property()
     */
    public ?string $keywords;

    /**
     * Image that describes the idea.
     * @var string|null
     * @OA\Property(type="string", format="binary")
     */
    public ?string $image;

    /**
     * Link to a resource that describes the idea.
     * @var string|null
     * @OA\Property()
     */
    public ?string $link;

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
