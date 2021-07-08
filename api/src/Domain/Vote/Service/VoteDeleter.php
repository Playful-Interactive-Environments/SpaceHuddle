<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete vote service.
 */
class VoteDeleter
{
    use ServiceDeleterTrait;
    use VoteServiceTrait;
}
