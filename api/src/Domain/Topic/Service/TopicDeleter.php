<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete topic service.
 */
class TopicDeleter
{
    use ServiceDeleterTrait;
    use TopicServiceTrait;
}
