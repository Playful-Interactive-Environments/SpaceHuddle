<?php

namespace PieLab\GAB\Models;

/**
 * Represents an idea.
 * @OA\Schema(description="total idea description")
 */
class Idea
{
    /**
     * The idea id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Current status of the idea.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/StateIdea")
     */
    public ?string $state;

    /**
     * Time of idea storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $timestamp;

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
     * Creates a new idea.
     * @param array|null $data The idea data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->state = strtoupper($data["state"] ?? null);
        $this->timestamp = $data["timestamp"] ?? null;
        $this->description = $data["description"] ?? null;
        $this->keywords = $data["keywords"] ?? null;
        $this->image = $data["image"] ?? null;
        $this->link = $data["link"] ?? null;
    }
}
