<?php


namespace App\Domain\Base\Service;

use App\Data\AuthorisationException;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;

/**
 * Description of the common read service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceReader extends AbstractService
{
    /**
     * Functionality of the read all service.
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
