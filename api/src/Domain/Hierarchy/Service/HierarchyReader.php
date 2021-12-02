<?php

namespace App\Domain\Hierarchy\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all categories for one task.
 */
class HierarchyReader
{
    use ServiceReaderTrait;
    use HierarchyServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateRead($data);
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
        $urlParameterName = array_key_first($data);
        if (str_ends_with($urlParameterName, "Id")) {
            $taskId = $data[$urlParameterName];
            $parentHierarchyId = null;
            if (array_key_exists("parentHierarchyId", $data)) {
                $parentHierarchyId = $data["parentHierarchyId"];
            }

            // Fetch data from the database
            return $this->repository->getAllForParent($taskId, $parentHierarchyId);
        }
        return null;
    }
}
