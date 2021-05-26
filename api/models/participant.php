<?php
require_once('avatar.php');
/**
 * @OA\Schema(description="participant description")
 */
class Participant {

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
    public ?string $browser_key;

    /**
     * To visually distinguish in the front end, each participant is assigned its own avatar.
     * @OA\Property(ref="#/components/schemas/Avatar")
     */
    public ?Avatar $avatar;


    /**
     * Encrypted IP address to hide IP addresses from the public while implementing an IP hash check function for a user account.
     * @var string|null
     * @OA\Property()
     */
    public ?string $ip_hash;


    /**
     * Authorization token to identify the participant.
     * @var string|null
     * @OA\Property()
     */
    public ?string $access_token;

    public function __construct(array $data = null, $token = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->browser_key = isset($data['browser_key']) ? $data['browser_key'] : null;
        $this->ip_hash = isset($data['ip_hash']) ? $data['ip_hash'] : null;
        $this->avatar = new Avatar($data);
        $this->access_token = $token;
    }
}

?>
