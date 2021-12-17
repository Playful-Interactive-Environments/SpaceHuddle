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
     * type of the tutorial step.
     * @var string|null
     * @OA\Property()
     */
    public ?string $type;

    /**
     * order of the tutorial step.
     * @var int|null
     * @OA\Property()
     */
    public ?int $order;

    /**
     * Creates a new tutorial step.
     * @param array $data Tutorial step data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->step = $reader->findString("step");
        $this->type = $reader->findString("type");
        $this->order = $reader->findInt("order");
    }
}
