<?php

namespace App\Domain\SessionRole\Data;

use Selective\ArrayReader\ArrayReader;

/**
 * Describes a user role for a session.
 * @OA\Schema(description="description of the user role for the session")
 */
class SessionRoleData
{
    /**
     * Authorized user.
     * @var string|null
     * @OA\Property()
     */
    public ?string $username;

    /**
     * Permission role for the session.
     * @var string|null
     * @OA\Property(ref="#/components/schemas/SessionRoleType")
     */
    public ?string $role;

    /**
     * Creates a new user role for a session.
     * @param array $data Selection data.
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);
        $this->username = $reader->findString("username");
        $this->role = strtoupper($reader->findString("role"));
    }
}
