<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Read infos for connection keys
 */
class SessionInfoReader
{
    use ServiceCreatorTrait;
    use SessionServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        $keys = $data["keys"];
        return $this->repository->getListByKeys($keys);
    }
}
