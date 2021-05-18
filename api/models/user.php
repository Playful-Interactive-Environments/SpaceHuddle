<?php
/**
 * @OA\Schema(description="topic description")
 */
class User {

    /**
     * The user id.
     * @var string
     * @OA\Property()
     */
    public $id;

    /**
     * The username of the user.
     * @var string
     * @OA\Property()
     */
    public $username;

    public function __construct(array $data = null)
    {
        $this->id = isset($data['id']) ? $data['id'] : null;
        $this->username = isset($data['username']) ? $data['username'] : null;
    }
}

?>
