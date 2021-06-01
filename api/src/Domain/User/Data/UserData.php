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
     * The user ID.
     * @var string|null
     * @OA\Property()
     */
    public ?string $id = null;

    /**
     * The username of the user.
     * @var string|null
     * @OA\Property()
     */
    public ?string $username = null;

    /**
     * The constructor.
     *
     * @param array $data The data
     */
    public function __construct(array $data = [])
    {
        $reader = new ArrayReader($data);

        $this->id = $reader->findString("id");
        $this->username = $reader->findString('username');
    }
}
