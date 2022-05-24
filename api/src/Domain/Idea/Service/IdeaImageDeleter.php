<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceDeleterTrait;

/**
 * Delete idea service.
 */
class IdeaImageDeleter
{
    use ServiceDeleterTrait;
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
        $this->repository->deleteImage($id);// Commit all changes
        return (object)[
            "state" => "Success",
            "message" => "image was successfully deleted."
        ];
    }
}
