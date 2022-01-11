<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseBodyServiceTrait;

/**
 * Service.
 */
final class UserForgetPassword
{
    use BaseBodyServiceTrait;
    use UserServiceJwtTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     * @throws GenericException
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validatePasswordReset($data);
        $token = $data["token"];
        $this->jwtAuth->validateToken($token);
        $tokenData = $this->jwtAuth->createParsedToken($token);
        $email =  $tokenData->claims()->get("username");
        $this->validator->validateUsernameExists($email);
        $action =  $tokenData->claims()->get("action");
        $this->validator->validateTokenAction($action, "reset");
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

        $token = $data["token"];
        $tokenData = $this->jwtAuth->createParsedToken($token);
        $email =  $tokenData->claims()->get("username");
        $this->repository->resetPassword($email, $data["password"]);

        return (object)[
            "state" => "Success",
            "message" => "The password was successfully updated."
        ];
    }
}
