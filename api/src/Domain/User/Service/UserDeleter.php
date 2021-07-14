<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Service.
 */
final class UserDeleter
{
    use ServiceDeleterTrait;
    use UserServiceTrait;
}
