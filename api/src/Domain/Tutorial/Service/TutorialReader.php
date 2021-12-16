<?php

namespace App\Domain\Tutorial\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all categories for one task.
 */
class TutorialReader
{
    use ServiceReaderTrait;
    use TutorialServiceTrait;

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
        return $this->repository->getAll('');
    }
}
