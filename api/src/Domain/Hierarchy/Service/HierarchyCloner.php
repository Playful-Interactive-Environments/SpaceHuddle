<?php

namespace App\Domain\Hierarchy\Service;

use App\Domain\Base\Service\ServiceClonerTrait;
use App\Domain\Idea\Service\IdeaServiceTrait;

/**
 * Topic create service.
 */
class HierarchyCloner
{
    use ServiceClonerTrait;
    use HierarchyServiceTrait;
}
