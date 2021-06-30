<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all topics for one session
 */
class TopicReader
{
    use ServiceReaderTrait;
    use TopicServiceTrait;
}
