<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete category service.
 */
class CategoryDeleter
{
    use ServiceDeleterTrait;
    use CategoryServiceTrait;
}
