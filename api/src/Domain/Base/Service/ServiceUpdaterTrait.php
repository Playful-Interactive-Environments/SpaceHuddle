<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;

/**
 * Description of the common update service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceUpdaterTrait
{
    use BaseServiceTrait;

    /**
     * Functionality of the update service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $data = array_merge($bodyData, $urlData);
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
