<?php


namespace App\Domain\Base\Service;

use App\Domain\Base\AbstractData;

/**
 * Description of the common read service functionality.
 * @package App\Domain\Base\Service
 */
class ServiceReader extends AbstractService
{
    /**
     * Functionality of the read service.
     *
     * @param string $id The entity id
     *
     * @return AbstractData The entity data
     */
    public function service(string $id): AbstractData
    {
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
