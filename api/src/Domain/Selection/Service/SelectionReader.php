<?php

namespace App\Domain\Selection\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all selections for one topic
 */
class SelectionReader
{
    use ServiceReaderTrait;
    use SelectionServiceTrait;
}
