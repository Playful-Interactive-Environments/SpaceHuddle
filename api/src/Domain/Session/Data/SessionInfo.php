<?php

namespace App\Domain\Session\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes a session.
 * @OA\Schema(description="session description")
 */
class SessionInfo
{
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
     * Creates a new Participant.
     * @param array $data Participant data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->title = $reader->findString("title");
        $this->connectionKey = $reader->findString("connection_key");
    }
}
