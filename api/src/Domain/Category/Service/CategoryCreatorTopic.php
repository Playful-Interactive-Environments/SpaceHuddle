<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;
use App\Domain\Idea\Type\IdeaState;
use App\Domain\Task\Type\TaskState;

/**
 * Category create service.
 */
class CategoryCreatorTopic
{
    use ServiceCreatorTrait;
    use CategoryServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateCreate($data);
        $this->validator->validateTopic($data["topicId"], []);
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
        return $this->repository->insertToTopic((object)$data, []);
    }
}
