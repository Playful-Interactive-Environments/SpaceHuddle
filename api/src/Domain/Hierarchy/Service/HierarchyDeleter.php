<?php

namespace App\Domain\Hierarchy\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete category service.
 */
class HierarchyDeleter
{
    use ServiceDeleterTrait;
    use HierarchyServiceTrait;
}
