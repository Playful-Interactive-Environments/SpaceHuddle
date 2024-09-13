<?php

namespace App\Domain\User\Type;

/**
 * Permission roles for the user.
 * @OA\Schema(
 *   description="Permission roles for the user",
 *   type="string",
 *   enum={"USER", "ADMIN"},
 *   example="USER"
 * )
 */
class UserRoleType
{
    /** @var string */
    public const USER = "user";

    /** @var string */
    public const ADMIN = "admin";
}
