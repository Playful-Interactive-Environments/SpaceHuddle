<?php

namespace App\Domain\Tutorial\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Read specific topic service
 */
class TutorialCreator
{
    use ServiceCreatorTrait;
    use TutorialServiceTrait;
}
