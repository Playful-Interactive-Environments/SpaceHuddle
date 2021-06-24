<?php

namespace App\Domain\Participant\Data;

use App\Domain\Base\Data\TokenData;

/**
 * Describes a participant with a token.
 * @OA\Schema(description="participant description")
 */
class ParticipantTokenData
{
    /**
     * Current participant.
     * @var ParticipantData
     * @OA\Property(ref="#/components/schemas/ParticipantData")
     */
    public ParticipantData $participant;

    /**
     * Current access token.
     * @var TokenData
     * @OA\Property(ref="#/components/schemas/TokenData")
     */
    public TokenData $token;

    /**
     * Creates a new Participant.
     * @param array $data Participant data.
     */
    public function __construct(ParticipantData $participant, TokenData $token)
    {
        $this->participant = $participant;
        $this->token = $token;
    }
}
