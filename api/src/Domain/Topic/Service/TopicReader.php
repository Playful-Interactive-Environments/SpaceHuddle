<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read ale topics for one session
 * @package App\Domain\Session\Service
 */
class TopicReader
{
    use ServiceReaderTrait;
    use TopicServiceTrait;
}
