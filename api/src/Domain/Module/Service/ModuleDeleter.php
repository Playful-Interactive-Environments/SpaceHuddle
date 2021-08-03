<?php

namespace App\Domain\Module\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete module service.
 */
class ModuleDeleter
{
    use ServiceDeleterTrait;
    use ModuleServiceTrait;
}
