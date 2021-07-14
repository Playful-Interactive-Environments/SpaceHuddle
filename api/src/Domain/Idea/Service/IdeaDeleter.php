<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete idea service.
 */
class IdeaDeleter
{
    use ServiceDeleterTrait;
    use IdeaServiceTrait;
}
