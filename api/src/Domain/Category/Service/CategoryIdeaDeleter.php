<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete ideas from category service.
 */
class CategoryIdeaDeleter
{
    use ServiceDeleterTrait;
    use CategoryServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateIdeas($data, true);
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
        $categoryId = $data["categoryId"];
        $ideas = $data["ideas"];
        $this->repository->deleteIdeas($categoryId, $ideas);
        return (object)[
            "state" => "Success",
            "message" => "Ideas were successfully removed."
        ];
    }
}
