<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;

/**
 * Description of the common delete service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceDeleterTrait
{
    use BaseServiceTrait;

    /**
     * Functionality of the delete service.
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
        $id = $urlData["id"];

        // Input validation
        $this->validator->validateExists($id);

        $this->transaction->begin();
        $this->repository->deleteById($id);// Commit all changes
        $this->transaction->commit();

        $entityName = $this->repository->getEntityName();
        return [
            "state" => "Success",
            "message" => "$entityName was successfully deleted."
        ];
    }
}
