<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Service\MailTrait;
use App\Domain\Base\Service\ServiceCreatorTrait;

/**
 * Service.
 */
final class UserCreator
{
    use ServiceCreatorTrait;
    use UserServiceJwtTrait;
    use MailTrait;

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
        // Insert entity and get new ID
        $result = $this->repository->insert((object)$data);

        $this->sendMailWithTokenUrl(
            $result->username,
            "Confirm your email for #application",
            "Click this link to confirm your email.",
            "confirm email",
            "confirm",
            "confirm"
        );
        return $result;
    }
}
