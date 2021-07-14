<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific session role service
 */
class SessionRoleSingleReader
{
    use ServiceSingleReaderTrait;
    use SessionRoleServiceTrait;

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     * @throws GenericException
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        $sessionId = $data["sessionId"];

        // Fetch data from the database
        return $this->repository->getById($sessionId);
    }
}
