<?php

namespace App\Domain\Tutorial\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Represents a tutorial.
 * @OA\Schema(description="tutorial description")
 */
class TutorialData
{
    /**
     * name of the tutorial step.
     * @var string|null
     * @OA\Property()
     */
    public ?string $step;

    /**
     * Creates a new idea.
     * @param array $data Idea data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->step = $reader->findString("step");
    }
}
