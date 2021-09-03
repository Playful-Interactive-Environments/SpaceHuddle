<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceReaderTrait;
use App\Domain\Task\Type\TaskState;
use function DI\string;

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
        $this->validator->validateTopic($data, [
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
            $order = null;
            if (array_key_exists("order", $data)) {
                $order = $data["order"];
            }
            $refId = null;
            if (array_key_exists("refId", $data)) {
                $refId = $data["refId"];
            }
            // Fetch data from the database
            return $this->repository->getAllOrderedFromTopic($topicId, $order, $refId);
        }
        return null;
    }
}
