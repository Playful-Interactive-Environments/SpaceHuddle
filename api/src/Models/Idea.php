<?php

namespace PieLab\GAB\Models;

/**
 * @OA\Schema(description="total idea description")
 */
class Idea {

    /**
     * The idea id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * current status of the idea
     * @var string|null
     * @OA\Property(ref="#/components/schemas/StateIdea")
     */
    public ?string $state;

    /**
     * Time of idea storage.
     * @var Date|string|null
     * @OA\Property(property="time_stamp")
     */
    public Date|string|null $time_stamp;

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
        $this->id = $data['id'] ?? null;
        $this->state = strtoupper($data['state'] ?? null);
        $this->time_stamp = $data['time_stamp'] ?? null;
        $this->description = $data['description'] ?? null;
        $this->keywords = $data['keywords'] ?? null;
        $this->image = $data['image'] ?? null;
        $this->link = $data['link'] ?? null;
    }
}

?>
