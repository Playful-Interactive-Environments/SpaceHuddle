<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\MailTrait;
use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Topic create service.
 */
class SessionRoleCreator
{
    use ServiceCreatorTrait;
    use SessionRoleServiceTrait;
    use MailTrait;

    /**
     * Executes the repository instructions assigned to the service.
     *
     * @param array $data Input data from the request.
     *
     * @return array|object|null Repository answer.
     * @throws GenericException
     */
    protected function serviceExecution(
        array $data
    ): array|object|null {
        $email = $data["username"];
        $sessionId = $data["sessionId"];
        $sessionName = $this->repository->getSessionName($sessionId);
        // Insert entity and get new ID
        $result = $this->repository->insert((object)$data);

        $this->sendMailWithUrl(
            $email,
            "You have been activated as co-moderator for a new session on #application",
            "Log in to join the session.",
            $sessionName,
            "session",
            $sessionId
        );
        return $result;
    }
}
