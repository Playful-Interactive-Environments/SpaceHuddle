<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseUrlServiceTrait;

/**
 * Read specific session service
 * @package App\Domain\Session\Service
 */
class SessionSingleReader
{
    use BaseUrlServiceTrait;
    use SessionServiceTrait;

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
        $id = $data["id"];
        return $this->repository->getById($id);
    }
}
