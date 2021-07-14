<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete session service.
 */
class SessionDeleter
{
    use ServiceDeleterTrait;
    use SessionServiceTrait;
}
