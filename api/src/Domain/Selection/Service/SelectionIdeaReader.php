<?php

namespace App\Domain\Selection\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all ideas for one selection.
 */
class SelectionIdeaReader
{
    use ServiceReaderTrait;
    use SelectionServiceTrait;

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
        if (array_key_exists("selectionId", $data)) {
            $categoryId = $data["selectionId"];
            // Fetch data from the database
            return $this->repository->getIdeas($categoryId);
        }
        return null;
    }
}
