<?php

namespace App\Domain\Idea\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents an idea.
 * @OA\Schema(description="total idea description")
 */
class IdeaData extends IdeaAbstract
{
    /**
     * Current status of the idea.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/IdeaState")
     */
    public ?string $state;

    /**
     * Time of idea storage.
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $timestamp;

    /**
     * Idea count.
     * @var int|null
     * @OA\Property()
     */
    public ?int $count;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        $reader = new ArrayReader($data);
        $this->state = $reader->findString("state");
        $this->timestamp = $reader->findString("timestamp");
        $this->count = $reader->findInt("count");
    }
}
