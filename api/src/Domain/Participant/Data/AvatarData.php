<?php

namespace App\Domain\Participant\Data;

use App\Domain\Base\Data\AbstractData;
use Selective\ArrayReader\ArrayReader;

/**
 * For visual differentiation in the frontend, each participant is assigned its own avatar.
 * @OA\Schema(description="For visual differentiation in the frontend, each participant is assigned its own avatar.",)
 */
class AvatarData extends AbstractData
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
     * Individual function for initial creation of properties
     * @param ArrayReader $reader The data
     */
    protected function initProperties(ArrayReader $reader) : void
    {
        $this->color = $reader->findString("color");
        $this->symbol = $reader->findString("symbol");
    }
}
