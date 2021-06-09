<?php


namespace App\Domain\Base\Service;

use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Data\AuthorisationData;

/**
 * Description of the common update service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceUpdater extends AbstractService
{
    /**
     * Functionality of the update service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws \App\Domain\Base\Data\AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);

        $id = $data["id"];

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
