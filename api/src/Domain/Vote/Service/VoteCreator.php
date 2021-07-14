<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Topic create service.
 */
class VoteCreator
{
    use ServiceCreatorTrait;
    use VoteServiceTrait;
}
