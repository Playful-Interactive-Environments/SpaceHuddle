<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceClonerTrait;

/**
 * Session create service.
 * @package App\Domain\Session\Service
 */
class SessionCloner
{
    use ServiceClonerTrait;
    use SessionServiceTrait;
}
