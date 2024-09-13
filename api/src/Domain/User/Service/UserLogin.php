<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Data\TokenData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseBodyServiceTrait;
use App\Domain\Base\Service\MailTrait;

/**
 * Service.
 */
final class UserLogin
{
    use BaseBodyServiceTrait;
    use UserServiceJwtTrait;
    use MailTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateLogin($data);
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
        $result = $this->repository->getUserByName($data["username"]);

        $jwt = $this->jwtAuth->createJwt(
            [
                "action" => "login",
                "userId" => $result->id,
                "username" => $result->username,
                "role" => $result->role
            ]
        );

        return new TokenData([
            "message" => "Successful login.",
            "accessToken" => $jwt,
            "tokenType" => "Bearer",
            "expiresIn" => $this->jwtAuth->getLifetime(),
            "parameter" => json_encode($result->parameter)
        ]);
    }
}
