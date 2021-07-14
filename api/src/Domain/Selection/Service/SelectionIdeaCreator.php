<?php

namespace App\Domain\Selection\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Add ideas to selection service.
 */
class SelectionIdeaCreator
{
    use ServiceCreatorTrait;
    use SelectionServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateIdeas($data);
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
        $selectionId = $data["selectionId"];
        $ideas = $data["ideas"];
        $this->repository->addIdeas($selectionId, $ideas);
        return (object)[
            "state" => "Success",
            "message" => "Ideas were successfully added."
        ];
    }
}
