<?php

namespace PieLab\GAB\Models;

/**
 * A reduced idea description for a voting result.
 * @OA\Schema(description="reduced idea description for voting result")
 */
class IdeaAbstract
{
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

    /**
     * Creates a new IdeaAbstract.
     * @param array|null $data IdeaAbstract data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->description = $data["description"] ?? null;
        $this->keywords = $data["keywords"] ?? null;
        $this->image = $data["image"] ?? null;
        $this->link = $data["link"] ?? null;
    }
}