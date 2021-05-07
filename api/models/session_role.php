<?php
/**
 * @OA\Schema(description="description of the user role for the session")
 */
class SessionRole {

    /**
     * Authorized user.
     * @var string
     * @OA\Property()
     */
    public $username;

    /**
     * Permission role for the session.
     * @var string
     * @OA\Property(ref="#/components/schemas/Role")
     */
    public $role;

    public function __construct(array $data = null)
    {
        $this->username = isset($data['username']) ? $data['username'] : null;
        $this->role = isset($data['role']) ? $data['role'] : null;
    }
}

?>
