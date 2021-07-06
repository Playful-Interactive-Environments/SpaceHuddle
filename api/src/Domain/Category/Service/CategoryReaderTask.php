<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all categories for one task.
 */
class CategoryReaderTask
{
    use ServiceReaderTrait;
    use CategoryServiceTrait;
}
