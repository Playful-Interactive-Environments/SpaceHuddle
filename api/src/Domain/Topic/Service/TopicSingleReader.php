<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class TopicSingleReader
{
    use ServiceSingleReaderTrait;
    use TopicServiceTrait;
}
