<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceReaderTrait;
use App\Domain\Task\Type\TaskState;

/**
 * Service to read all ideas for one task.
 */
class IdeaReaderTopic
{
    use ServiceReaderTrait;
    use IdeaServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateTopic($data["topicId"], [
            strtoupper(TaskState::ACTIVE),
            strtoupper(TaskState::READ_ONLY)
        ]);
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     * @throws GenericException
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        if (array_key_exists("topicId", $data)) {
            $topicId = $data["topicId"];
            // Fetch data from the database
            return $this->repository->getAllFromTopic($topicId);
        }
        return null;
    }
}
