<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all participants for one session.
 */
class SessionTemplateReader
{
    use ServiceReaderTrait;
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
        return $this->repository->getTemplates();
    }
}
