<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all session roles for one session
 */
class SessionRoleReader
{
    use ServiceReaderTrait;
    use SessionRoleServiceTrait;
}
