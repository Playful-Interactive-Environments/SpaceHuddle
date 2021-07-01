<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class IdeaSingleReader
{
    use ServiceSingleReaderTrait;
    use IdeaServiceTrait;
}
