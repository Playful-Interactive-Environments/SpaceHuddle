<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceUpdaterTrait;

/**
 * Update session role service.
 */
class SessionRoleUpdater
{
    use ServiceUpdaterTrait;
    use SessionRoleServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        // Input validation
        $this->validator->validateUpdate($data);
    }
}
