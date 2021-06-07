<?php


namespace App\Domain\Base\Service;

use App\Domain\Base\AbstractData;

/**
 * Description of the common update service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceUpdater extends AbstractService
{
    /**
     * Functionality of the update service.
     *
     * @param string $id The entity id
     * @param array $data Data to be modified
     *
     * @return void
     */
    public function service(string $id, array $data): mixed
    {
        // Input validation
        $this->validator->validateUpdate($id, $data);

        // Validation was successfully
        $user = (object)$data;
        $user->id = $id;

        // Update the user
        $result = $this->repository->update($user);

        // Logging
        $entityName = $this->repository->getEntityName();
        $this->logger->info("$entityName updated successfully: $id");

        return $result;
    }
}
