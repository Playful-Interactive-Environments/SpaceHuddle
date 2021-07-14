<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update vote service.
 */
class VoteUpdater
{
    use ServiceUpdaterTrait;
    use VoteServiceTrait;
}
