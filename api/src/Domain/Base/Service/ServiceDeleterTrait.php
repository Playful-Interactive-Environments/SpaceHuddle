<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationException;
use App\Data\AuthorisationData;
use App\Domain\Base\Repository\GenericException;

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
     * @throws AuthorisationException|GenericException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $this->checkPermission($authorisation, $urlData);
        $data = array_merge($bodyData, $urlData);

        $id = $data["id"];

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
