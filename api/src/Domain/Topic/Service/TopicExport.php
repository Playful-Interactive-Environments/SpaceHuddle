<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\BaseUrlServiceTrait;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

/**
 * Read specific topic service
 */
class TopicExport
{
    use BaseUrlServiceTrait;
    use TopicServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateExport($data);
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     * @throws Exception
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        $id = $data["id"];
        $exportType = "xlsx";
        if (array_key_exists("exportType", $data)) {
            $exportType = $data["exportType"];
        }
        return $this->repository->export($id, $exportType);
    }
}
