<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationException;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;

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
     * @throws AuthorisationException
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

        $this->transaction->begin();
        // Update the user
        $result = $this->repository->update($user);
        $this->transaction->commit();

        // Logging
        $entityName = $this->repository->getEntityName();
        $this->logger->info("$entityName updated successfully: $id");

        return $result;
    }
}
