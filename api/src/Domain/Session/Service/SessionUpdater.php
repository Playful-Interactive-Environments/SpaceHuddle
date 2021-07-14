<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ServiceUpdaterTrait;
use App\Domain\Session\Type\SessionRoleType;

/**
 * Update session service.
 */
class SessionUpdater
{
    use ServiceUpdaterTrait {
        ServiceUpdaterTrait::serviceExecution as private genericService;
    }
    use SessionServiceTrait;

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
        $result = $this->genericService($data);
        $result->role = strtoupper(SessionRoleType::MODERATOR);
        return $result;
    }
}
