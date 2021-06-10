<?php

namespace App\Domain\Participant\Data;

use App\Domain\Base\Data\AbstractData;
use Selective\ArrayReader\ArrayReader;

/**
 * Describes a participant.
 * @OA\Schema(description="participant description")
 */
class ParticipantData extends AbstractData
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
     * Individual function for initial creation of properties
     * @param ArrayReader $reader The data
     */
    protected function initProperties(ArrayReader $reader) : void
    {
        $this->id = $reader->findString("id");
        $this->browserKey = $reader->findString("browser_key");
        $this->state = $reader->findString("state");
        $this->ipHash = $reader->findInt("ip_hash");
        $this->avatar =  $reader->findString("expiration_date");
        $this->accessToken = $reader->findString("creation_date");
    }

    /**
     * Creates a new Participant.
     * @param array|null $data Participant data.
     * @param null $token Authorization token.
     */
    public function __construct(array $data = null, $token = null)
    {
        parent::__construct($data);
        $this->avatar = new AvatarData($data);
        $this->accessToken = $token;
    }
}
