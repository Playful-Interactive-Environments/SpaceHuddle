<?php

namespace PieLab\GAB\Models;

/**
 * Represents a user.
 * @OA\Schema(description="User description")
 */
class User
{
    /**
     * The user ID.
     * @var string|null
     * @OA\Property()
     */
    public ?string $id;

    /**
     * The username of the user.
     * @var string|null
     * @OA\Property()
     */
    public ?string $username;

    /**
     * Creates a new user.
     * @param array|null $data User data.
     */
    public function __construct(array $data = null)
    {
        $this->id = $data["id"] ?? null;
        $this->username = $data["username"] ?? null;
    }
}
