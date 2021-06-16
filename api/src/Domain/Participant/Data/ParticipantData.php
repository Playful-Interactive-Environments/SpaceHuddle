<?php

namespace App\Domain\Participant\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes a participant.
 * @OA\Schema(description="participant description")
 */
class ParticipantData
{
    /**
     * The participant id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * Unique key to assign a browser connection to a user.
     * @var string|null
     * @OA\Property()
     */
    public ?string $browserKey;

    /**
     * Current status of the participant.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/ParticipantState")
     */
    public ?string $state;

    /**
     * To visually distinguish in the front end, each participant is assigned its own avatar.
     * @OA\Property(ref="#/components/schemas/AvatarData")
     */
    public ?AvatarData $avatar;

    /**
     * Creates a new Participant.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->browserKey = $reader->findString("browser_key");
        $this->state = $reader->findString("state");
        $this->avatar = new AvatarData($data);
    }
}
