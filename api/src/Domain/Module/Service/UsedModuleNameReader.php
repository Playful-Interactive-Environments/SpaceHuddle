<?php

namespace App\Domain\Module\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all modules for one task
 */
class UsedModuleNameReader
{
    use ServiceReaderTrait;
    use ModuleServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->usedValidator($data);
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
        $taskType = null;
        if (array_key_exists("taskType", $data)) {
            $taskType = $data["taskType"];
        }

        if (isset($taskType)) {
            // Fetch data from the database
            return $this->repository->getUserModules($taskType);
        }
        return null;
    }
}
