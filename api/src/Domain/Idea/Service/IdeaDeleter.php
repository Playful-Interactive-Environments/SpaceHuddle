<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete topic service.
 */
class IdeaDeleter
{
    use ServiceDeleterTrait;
    use IdeaServiceTrait;
}
