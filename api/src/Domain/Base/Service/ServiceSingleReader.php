<?php

namespace App\Domain\Base\Service;

use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Data\AuthorisationData;

/**
 * Description of the common read service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceSingleReader extends AbstractService
{
    /**
     * Functionality of the read single entity service.
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
        // ...

        // Fetch data from the database
        $result = $this->repository->getById($id);

        // Optional: Add or invoke your complex business logic here
        // ...

        // Optional: Map result
        // ...

        return $result;
    }
}
