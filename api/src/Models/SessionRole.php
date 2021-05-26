<?php

namespace PieLab\GAB\Models;

/**
 * @OA\Schema(description="description of the user role for the session")
 */
class SessionRole {

    /**
     * Authorized user.
     * @var string|null
     * @OA\Property()
     */
    public ?string $username;

    /**
     * Permission role for the session.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/Role")
     */
    public ?string $role;

    public function __construct(array $data = null)
    {
        $this->username = $data['username'] ?? null;
        $this->role = $data['role'] ?? null;
    }
}

?>
