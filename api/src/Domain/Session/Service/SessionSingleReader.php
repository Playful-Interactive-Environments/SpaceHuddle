<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific session service
 * @package App\Domain\Session\Service
 */
class SessionSingleReader
{
    use ServiceSingleReaderTrait;
    use SessionServiceTrait;
}
