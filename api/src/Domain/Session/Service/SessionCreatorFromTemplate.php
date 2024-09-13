<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Session create service.
 * @package App\Domain\Session\Service
 */
class SessionCreatorFromTemplate
{
    use ServiceCreatorTrait;
    use SessionServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateCreateFromTemplate($data);
    }

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     * @param string $templateId Clone template ID.
     *
     * @return array|object|null Repository answer.
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        // Insert entity and get new ID
        return $this->repository->createFromTemplate((object)$data);
    }
}
