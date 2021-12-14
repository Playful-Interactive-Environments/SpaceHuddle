<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceCreatorTrait;
use App\Domain\Session\Repository\SessionRepository;

/**
 * Topic create service.
 */
class SessionRoleCreator
{
    use ServiceCreatorTrait;
    use SessionRoleServiceTrait;

    // Application settings
    private function settings() {
        return require __DIR__ . "/../../../../config/settings.php";
    }

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

        $applicationSettings = (object)$this->settings()["application"];
        $resetUrl = "$applicationSettings->baseUrl$applicationSettings->session";

        $message = "
            <h1>You have been activated as co-moderator for a new session on spacehuddle.io</h1>
            <div>log in to join the session</div>
            <div>
                <a href='$resetUrl$sessionId' >$sessionName</a>
            </div>";

        mail($email, 'You have been activated as co-moderator for a new session on spacehuddle.io', $message);
        return $result;
    }
}
