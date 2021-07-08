<?php

namespace App\Domain\User\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Data Model for User.
 * @OA\Schema(description="User description")
 */
final class UserData
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
     * Creates a new User.
     * @param array $data User data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->id = $reader->findString("id");
        $this->username = $reader->findString("username");
    }
}
