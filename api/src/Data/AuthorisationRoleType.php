<?php

namespace App\Data;

/**
 * User role based on the given token.
 * @package App\Data
 */
class AuthorisationRoleType
{
    public const USER = "user";
    public const PARTICIPANT = "participant";
    public const PARTICIPANT_INACTIVE = "participant_inactive";
    public const NONE = "none";
}
