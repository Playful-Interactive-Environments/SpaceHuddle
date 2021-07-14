<?php

namespace App\Domain\Selection\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Selection description.
 * @OA\Schema(description="description of the selection group naming")
 */
class SelectionData
{
    /**
     * The selection id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * The selection name.
     * @var string|null
     * @OA\Property()
     */
    public ?string $name;

    /**
     * Creates a new selection.
     * @param array $data Selection data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->name = $reader->findString("name");
    }
}
