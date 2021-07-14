<?php

namespace App\Domain\Base\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes token information.
 * @OA\Schema(description="token description")
 */
class TokenData
{
    /**
     * Success message.
     * @var string|null
     * @OA\Property(example="Successful login.")
     */
    public ?string $message;

    /**
     * The access token.
     * @var string|null
     * @OA\Property(example="reallylongtokenstring")
     */
    public ?string $accessToken;

    /**
     * The token type.
     * @var string|null
     * @OA\Property(example="Bearer")
     */
    public ?string $tokenType;

    /**
     * The token expiration in seconds.
     * @var int|null
     * @OA\Property(example="86400")
     */
    public ?int $expiresIn;

    /**
     * Creates a new Token.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->message = $reader->findString("message");
        $this->accessToken = $reader->findString("accessToken");
        $this->tokenType = $reader->findString("tokenType");
        $this->expiresIn = $reader->findInt("expiresIn");
    }
}
