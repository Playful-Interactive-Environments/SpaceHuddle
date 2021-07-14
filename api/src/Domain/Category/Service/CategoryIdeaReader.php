<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all ideas for one category.
 */
class CategoryIdeaReader
{
    use ServiceReaderTrait;
    use CategoryServiceTrait;

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
        if (array_key_exists("categoryId", $data)) {
            $categoryId = $data["categoryId"];
            // Fetch data from the database
            return $this->repository->getIdeas($categoryId);
        }
        return null;
    }
}
