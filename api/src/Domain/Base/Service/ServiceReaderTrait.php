<?php

namespace App\Domain\Base\Service;

/**
 * Description of the common read service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceReaderTrait
{
    use BaseUrlServiceTrait;

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
        $urlParameterName = array_key_first($data);
        if (str_ends_with($urlParameterName, "Id")) {
            $parentId = $data[$urlParameterName];

            // Fetch data from the database
            return $this->repository->getAll($parentId);
        }
        return null;
    }
}
