<?php

namespace App\Domain\Task\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Tasks enable themselves depending on their dependency parameters.
 * @OA\Schema(description="Tasks enable themselves depending on their dependency parameters.",)
 */
class DependencyData
{
    /**
     * Dependency start timestamp.
     * @var int
     * @OA\Property()
     */
    public int $start;

    /**
     * Dependency duration timestamp.
     * @var int
     * @OA\Property()
     */
    public int $duration;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->start = $reader->findString("dependenceStart");
        $this->duration = $reader->findString("dependenceDuration");
    }
}
