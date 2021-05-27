<?php

namespace PieLab\GAB\Models;

/**
 * For visual differentiation in the frontend, each participant is assigned its own avatar.
 * @OA\Schema(description="For visual differentiation in the frontend, each participant is assigned its own avatar.",)
 */
class Avatar
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
     * Create a new avatar.
     * @param array|null $data Configuration data (color and symbol).
     */
    public function __construct(array $data = null)
    {
        $this->color = $data["color"] ?? null;
        $this->symbol = $data["symbol"] ?? null;
    }
}
