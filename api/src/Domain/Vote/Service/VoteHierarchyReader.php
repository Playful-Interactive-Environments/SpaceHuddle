<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all votes for one hierarchy idea
 */
class VoteHierarchyReader
{
    use ServiceReaderTrait;
    use VoteServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateHierarchyRead($data);
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
        if (array_key_exists("parentId", $data)) {
            $parentId = $data["parentId"];
            // Fetch data from the database
            return $this->repository->getAllForHierarchy($parentId);
        }
        return null;
    }
}
