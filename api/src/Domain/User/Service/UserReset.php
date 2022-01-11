<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseBodyServiceTrait;
use App\Domain\Base\Service\MailTrait;

/**
 * Service.
 */
final class UserReset
{
    use BaseBodyServiceTrait;
    use UserServiceJwtTrait;
    use MailTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     * @throws GenericException
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateUsernameExists($data["email"]);
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
        // Insert user and get new user ID
        $result = $this->repository->getUserByName($data["email"]);

        $this->sendMailWithTokenUrl(
            $result->username,
            "Forget password for spacehuddle.io",
            "Click this link to reset your password.",
            "reset password",
            "forgetPassword",
            "reset"
        );

        return [
            "message" => "Successful send mail",
            "expiresIn" => $this->jwtAuth->getLifetime()
        ];
    }
}
