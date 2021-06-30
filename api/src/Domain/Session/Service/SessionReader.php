<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionReader
{
    use ServiceReaderTrait;
    use SessionServiceTrait;
}
