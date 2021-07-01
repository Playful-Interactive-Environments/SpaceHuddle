<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all ideas for one task.
 */
class IdeaReaderTask
{
    use ServiceReaderTrait;
    use IdeaServiceTrait;
}
