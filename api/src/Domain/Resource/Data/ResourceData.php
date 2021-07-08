<?php

namespace App\Domain\Resource\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes a resource.
 * @OA\Schema(description="resource description")
 */
class ResourceData
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
     * Creates a new vote.
     * @param array $data Vote data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->title = $reader->findString("title");
        $this->image = $reader->findString("image");
        $this->link = $reader->findString("link");
    }
}
