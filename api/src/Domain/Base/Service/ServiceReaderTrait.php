<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;

/**
 * Description of the common read service functionality.
 * @package App\Domain\Base\Service
 */
trait ServiceReaderTrait
{
    use BaseServiceTrait;

    /**
     * Functionality of the read all service.
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

        $parentId = $data["parentId"];
        // Input validation
        // ...

        // Fetch data from the database
        $result = $this->repository->getAll($parentId);

        // Optional: Add or invoke your complex business logic here
        // ...

        // Optional: Map result
        // ...

        return $result;
    }
}
