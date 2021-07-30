<?php

namespace App\Domain\Participant\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * For visual differentiation in the frontend, each participant is assigned its own avatar.
 * @OA\Schema(description="For visual differentiation in the frontend, each participant is assigned its own avatar.",)
 */
class AvatarData
{
    /**
     * The avatar color.
     * @var string|null
     * @OA\Property(example="red")
     */
    public ?string $color;

    /**
     * The avatar symbol.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/AvatarSymbol")
     */
    public ?string $symbol;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->color = $reader->findString("color");
        $this->symbol = $reader->findString("symbol");
    }

    public function toString() {
        return "$this->symbol$this->color";
    }
}
