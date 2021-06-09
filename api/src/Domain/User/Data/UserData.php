<?php

namespace App\Domain\User\Data;

use App\Domain\Base\Data\AbstractData;
use Selective\ArrayReader\ArrayReader;

/**
 * Data Model for User.
 * @OA\Schema(description="User description")
 */
final class UserData extends AbstractData
{
    /**
     * The entity ID.
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
     * Individual function for initial creation of properties
     * @param ArrayReader $reader The data
     */
    protected function initProperties(ArrayReader $reader) : void
    {
        $this->id = $reader->findString("id");
        $this->username = $reader->findString("username");
    }
}
