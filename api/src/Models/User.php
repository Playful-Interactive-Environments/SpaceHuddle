<?php

namespace PieLab\GAB\Models;

/**
 * @OA\Schema(description="topic description")
 */
class User
{
    /**
     * The user id.
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

    public function __construct(array $data = null)
    {
        $this->id = $data['id'] ?? null;
        $this->username = $data['username'] ?? null;
    }
}
