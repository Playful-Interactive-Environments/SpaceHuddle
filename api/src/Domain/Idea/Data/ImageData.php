<?php

namespace App\Domain\Idea\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Idea image description.
 * @OA\Schema(description="idea image description")
 */
class ImageData
{
    /**
     * The idea id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Image that describes the idea.
     * @var string|null
     * @OA\Property(type="string", format="binary")
     */
    public ?string $image;

    /**
     * Time of image storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $imageTimestamp;

    /**
     * Creates a new image of an idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->image = $reader->findString("image");
        $this->imageTimestamp = $reader->findString("image_timestamp");
    }
}
