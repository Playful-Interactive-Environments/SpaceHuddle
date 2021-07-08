<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Read all session service
 */
class SessionReader
{
    use ServiceReaderTrait;
    use SessionServiceTrait;
}
