<?php

namespace App\Domain\User\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Data Model for User.
 * @OA\Schema(description="User description")
 */
final class UserAdminData
{
    /**
     * The entity ID.
     * @var string|null
     * @OA\Property(example="uuid")
     */
    public ?string $id = null;

    /**
     * The username of the user.
     * @var string|null
     * @OA\Property()
     */
    public ?string $username = null;

    /**
     * When was the session created?
     * @var string|null
     * @OA\Property(format="date")
     */
    public ?string $creationDate;

    /**
     * EMail is confirmed.
     * @var bool|null
     * @OA\Property()
     */
    public ?bool $confirmed = null;

    /**
     * Own sessions count.
     * @var int|null
     * @OA\Property()
     */
    public ?int $ownSessions = null;

    /**
     * Shared sessions count.
     * @var int|null
     * @OA\Property()
     */
    public ?int $sharedSessions = null;

    /**
     * Creates a new User.
     * @param array $data User data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->username = $reader->findString("username");
        $this->creationDate = $reader->findString("creation_date");
        $this->confirmed = $reader->findBool("confirmed");
        $this->ownSessions = $reader->findInt("own_sessions");
        $this->sharedSessions = $reader->findInt("shared_sessions");
    }
}
