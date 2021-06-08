<?php


namespace App\Domain\Base\Service;

use App\Domain\Base\AbstractData;

/**
 * Description of the common insert service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceCreator extends AbstractService
{
    /**
     * Functionality of the create service.
     *
     * @param array<string, mixed> $data The form data
     *
     * @return AbstractData|null Result entity
     */
    public function service(array $data): AbstractData|null
    {
        // Input validation
        $this->validator->validateCreate($data);

        // Insert entity and get new ID
        $result = $this->repository->insert((object)$data);

        // Logging
        $entityName = $this->repository->getEntityName();
        $this->logger->info("$entityName created successfully: $result->id");

        return $result;
    }
}
