<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceSingleReaderTrait;

/**
 * Read specific topic service
 */
class IdeaImageReader
{
    use ServiceSingleReaderTrait;
    use IdeaServiceTrait;

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
        $id = $data["id"];

        // Fetch data from the database
        return $this->repository->getImage($id);
    }
}
