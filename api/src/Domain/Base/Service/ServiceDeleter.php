<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationException;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
use PDOException;

/**
 * Description of the common delete service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceDeleter extends AbstractService
{
    /**
     * Functionality of the delete service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|AbstractData|null {
        parent::service($authorisation, $bodyData, $urlData);
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
