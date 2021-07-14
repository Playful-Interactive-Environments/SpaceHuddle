<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific vote service
 */
class VoteSingleReader
{
    use ServiceSingleReaderTrait;
    use VoteServiceTrait;
}
