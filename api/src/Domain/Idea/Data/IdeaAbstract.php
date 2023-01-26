<?php

namespace App\Domain\Idea\Data;

use Selective\ArrayReader\ArrayReader;

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
     * Link to a resource that describes the idea.
     * @var string|null
     * @OA\Property()
     */
    public ?string $link;

    /**
     * Variable json parameters depending on the task type.
     * @var object|null
     * @OA\Property(type="object", format="json")
     */
    public ?object $parameter;

    /**
     * Planned task order.
     * @var int|null
     * @OA\Property(example=1)
     */
    public ?int $order;

    /**
     * Creates a new abstract of an idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->description = $reader->findString("description");
        $this->keywords = $reader->findString("keywords");
        $this->link = $reader->findString("link");
        $this->parameter = (object)json_decode($reader->findString("parameter") ?? "{}");
        $this->order = $reader->findInt("order");
    }
}
