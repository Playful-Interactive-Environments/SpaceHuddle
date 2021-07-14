<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Read public screen service
 */
class PublicScreenReader
{
    use ServiceReaderTrait;
    use SessionServiceTrait;

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
        return $this->repository->getPublicScreen($data["sessionId"]);
    }
}
