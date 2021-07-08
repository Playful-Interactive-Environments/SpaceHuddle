<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update public screen service.
 */
class PublicScreenUpdater
{
    use ServiceUpdaterTrait;
    use SessionServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validatePublicScreenUpdate($data);
    }

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
        return $this->repository->setPublicScreen($data["sessionId"], $data["taskId"]);
    }
}
