<?php


namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update idea service.
 */
class IdeaImageUpdater
{
    use ServiceUpdaterTrait;
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
        $this->repository->setImage((object)$data);
        return null;
    }
}
