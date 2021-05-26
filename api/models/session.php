<?php
/**
 * @OA\Schema(description="session description")
 */
class Session {

    /**
     * The session id.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id;

    /**
     * The session title.
     * @var string|null
     * @OA\Property()
     */
    public ?string $title;

    /**
     * The key with which the participants can connect to the session.
     * @var string|null
     * @OA\Property(example="ABCD1234")
     */
    public ?string $connection_key;

    /**
     * What is the maximum number of users allowed to participate in the session?
     * @var int|null
     * @OA\Property(example=1000)
     */
    public ?int $max_participants;

    /**
     * How long is the session valid?
     * @var Date|string|null
     * @OA\Property(property="expiration_date")
     */
    public Date|string|null $expiration_date;

    /**
     * When was the session created?
     * @var Date|string|null
     * @OA\Property(property="creation_date")
     */
    public Date|string|null $creation_date;

    /**
     * role in the session
     * @var string|null
     * @OA\Property(example=null)
     */
    public ?string $public_screen_module_id;

    /**
     * role in the session
     * @var string|null
     * @OA\Property()
     */
    public ?string $role;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->title = isset($data['title']) ? $data['title'] : null;
        $this->connection_key = isset($data['connection_key']) ? $data['connection_key'] : null;
        $this->max_participants = isset($data['max_participants']) ? (int)$data['max_participants'] : null;
        $this->expiration_date = isset($data['expiration_date']) ? $data['expiration_date'] : null;
        $this->creation_date = isset($data['creation_date']) ? $data['creation_date'] : null;
        $this->public_screen_module_id = isset($data['public_screen_module_id']) ? $data['public_screen_module_id'] : null;
        $this->role = strtoupper(isset($data['role']) ? $data['role'] : null);
    }
}

?>
