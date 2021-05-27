<?php

namespace PieLab\GAB\Models;

/**
 * Describes a session.
 * @OA\Schema(description="session description")
 */
class Session
{
    /**
     * The session ID.
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
    public ?int $maxParticipants;

    /**
     * How long is the session valid?
     * @var Date|string|null
     * @OA\Property(property="expiration_date")
     */
    public Date|string|null $expirationDate;
    // TODO: Check class Date.

    /**
     * When was the session created?
     * @var Date|string|null
     * @OA\Property(property="creation_date")
     */
    public Date|string|null $creationDate;

    /**
     * Public screen module ID.
     * @var string|null
     * @OA\Property(example=null)
     */
    public ?string $publicScreenModuleId;

    /**
     * Role in the session.
     * @var string|null
     * @OA\Property()
     */
    public ?string $role;

    /**
     * Creates a new session.
     * @param array|null $data Session data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->title = $data["title"] ?? null;
        $this->connection_key = $data["connection_key"] ?? null;
        $this->maxParticipants = (int)$data["max_participants"] ?? null;
        $this->expirationDate = $data["expiration_date"] ?? null;
        $this->creationDate = $data["creation_date"] ?? null;
        $this->publicScreenModuleId = $data["public_screen_module_id"] ?? null;
        $this->role = strtoupper($data["role"] ?? null);
    }
}
