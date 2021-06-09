<?php

namespace App\Domain\Session\Data;

use App\Domain\Base\Data\AbstractData;
use Selective\ArrayReader\ArrayReader;

/**
 * Describes a session.
 * @OA\Schema(description="session description")
 */
class SessionData extends AbstractData
{
    /**
     * The entity ID.
     * @var string|null
     * @OA\Property()
     */
    public ?string $id = null;

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
    public ?string $connectionKey;

    /**
     * What is the maximum number of users allowed to participate in the session?
     * @var int|null
     * @OA\Property(example=1000)
     */
    public ?int $maxParticipants;

    /**
     * How long is the session valid?
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $expirationDate;

    /**
     * When was the session created?
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $creationDate;

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
     * Individual function for initial creation of properties
     * @param ArrayReader $reader The data
     */
    protected function initProperties(ArrayReader $reader) : void
    {
        $this->id = $reader->findString("id");
        $this->title = $reader->findString("title");
        $this->connectionKey = $reader->findString("connection_key");
        $this->maxParticipants = $reader->findInt("max_participants");
        $this->expirationDate = $reader->findString("expiration_date");
        $this->creationDate = $reader->findString("creation_date");
        $this->publicScreenModuleId = $reader->findString("public_screen_module_id");
        $this->role = $reader->findString("role");

        if (isset($this->role)) {
            $this->role = strtoupper($this->role);
        }
    }
}
