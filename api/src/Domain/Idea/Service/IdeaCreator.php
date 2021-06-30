<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;
use App\Domain\Idea\Type\IdeaState;

/**
 * Idea create service.
 */
class IdeaCreator
{
    use ServiceCreatorTrait {
        ServiceCreatorTrait::serviceExecution as private creatorService;
    }
    use IdeaServiceTrait;

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        $data["state"] = strtoupper(IdeaState::NEW);
        return $this->creatorService($data);
    }
}
