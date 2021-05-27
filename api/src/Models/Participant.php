<?php

namespace PieLab\GAB\Models;

/**
 * Describes a participant.
 * @OA\Schema(description="participant description")
 */
class Participant
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
     * @OA\Property(ref="#/components/schemas/StateParticipant")
     */
    public ?string $state;

    /**
     * To visually distinguish in the front end, each participant is assigned its own avatar.
     * @OA\Property(ref="#/components/schemas/Avatar")
     */
    public ?Avatar $avatar;

    /**
     * Encrypted IP address to hide IP addresses from the public while implementing an IP hash check function for a user
     * account.
     * @var string|null
     * @OA\Property()
     */
    public ?string $ipHash;

    /**
     * Authorization token to identify the participant.
     * @var string|null
     * @OA\Property()
     */
    public ?string $accessToken;

    /**
     * Creates a new Participant.
     * @param array|null $data Participant data.
     * @param null $token Authorization token.
     */
    public function __construct(array $data = null, $token = null)
    {
        $this->id = $data["id"] ?? null;
        $this->browserKey = $data["browser_key"] ?? null;
        $this->state = strtoupper($data["state"] ?? null);
        $this->ipHash = $data["ip_hash"] ?? null;
        $this->avatar = new Avatar($data);
        $this->accessToken = $token;
    }
}
