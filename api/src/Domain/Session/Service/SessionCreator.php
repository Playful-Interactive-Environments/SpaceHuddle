<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Session create service.
 * @package App\Domain\Session\Service
 */
class SessionCreator
{
    use ServiceCreatorTrait;
    use SessionServiceTrait;
}
