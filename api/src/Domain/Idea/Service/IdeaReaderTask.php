<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all ideas for one task.
 */
class IdeaReaderTask
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
            $parentId = $data[$urlParameterName];
            $order = null;
            if (array_key_exists("order", $data)) {
                $order = $data["order"];
            }
            $refId = null;
            if (array_key_exists("refId", $data)) {
                $refId = $data["refId"];
            }

            // Fetch data from the database
            return $this->repository->getAllOrdered($parentId, $order, $refId);
        }
        return null;
    }
}
