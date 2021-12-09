<?php

namespace App\Domain\View\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class ViewSingleReader
{
    use ServiceSingleReaderTrait;
    use ViewServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateDetailRead($data);
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
        $type = null;
        if (array_key_exists("type", $data)) {
            $type = $data["type"];
        }
        $typeId = null;
        if (array_key_exists("typeId", $data)) {
            $typeId = $data["typeId"];
        }
        $order = null;
        if (array_key_exists("order", $data)) {
            $order = $data["order"];
        }
        $refId = null;
        if (array_key_exists("refId", $data)) {
            $refId = $data["refId"];
        }
        $filter = null;
        if (array_key_exists("filter", $data)) {
            $filter = json_decode($data["filter"]);
        }

        if (isset($type) and isset($typeId)) {
            // Fetch data from the database
            return $this->repository->getDetails($type, $typeId, $order, $refId, $filter);
        }
        return null;
    }
}
