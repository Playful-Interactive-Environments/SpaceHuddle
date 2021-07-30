<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;
use App\Domain\Idea\Type\IdeaState;
use App\Domain\Task\Type\TaskState;

/**
 * Idea create service.
 */
class IdeaCreatorTopic
{
    use ServiceCreatorTrait {
        ServiceCreatorTrait::serviceExecution as private creatorService;
    }
    use IdeaServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateCreate($data);
        $this->validator->validateTopic($data, [strtoupper(TaskState::ACTIVE)]);
    }

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
        // Insert entity and get new ID
        return $this->repository->insertToTopic((object)$data, [strtoupper(TaskState::ACTIVE)]);
    }
}
