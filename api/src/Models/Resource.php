<?php

namespace PieLab\GAB\Models;

/**
 * Describes a resource.
 * @OA\Schema(description="resource description")
 */
class Resource
{
    /**
     * The resource ID.
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

    /**
     * Creates a new resource.
     * @param array|null $data Resource data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->title = $data["title"] ?? null;
        $this->image = $data["image"] ?? null;
        $this->link = $data["link"] ?? null;
    }
}
