<?php

namespace App\Domain\User\Service;

use App\Database\TransactionInterface;
use App\Domain\Base\Data\TokenData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\BaseBodyServiceTrait;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use App\Routing\JwtAuth;

/**
 * Service.
 */
final class UserReset
{
    use BaseBodyServiceTrait;

    protected UserRepository $repository;
    protected UserValidator $validator;
    protected JwtAuth $jwtAuth;

    // Application settings
    private function settings() {
        return require __DIR__ . "/../../../../config/settings.php";
    }

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     * @param JwtAuth $jwtAuth The jwt authorization
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory,
        JwtAuth $jwtAuth
    ) {
        $this->setUp($transaction, $loggerFactory);
        $this->repository = $repository;
        $this->validator = $validator;
        $this->jwtAuth = $jwtAuth;
    }

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

        $jwt = $this->jwtAuth->createJwt(
            [
                "action" => "reset",
                "username" => $result->username
            ]
        );

        $applicationSettings = (object)$this->settings()["application"];
        $resetUrl = "$applicationSettings->baseUrl$applicationSettings->forgetPassword";

        $nachricht = "
            <h1>Forget password for spacehuddle.io</h1>
            <div>click this link to reset your password.</div>
            <div>
                <a href='$resetUrl$jwt' >reset password</a>
            </div>";

        mail($result->username, 'Forget password for spacehuddle.io', $nachricht);

        return [
            "message" => "Successful send mail",
            "expiresIn" => $this->jwtAuth->getLifetime()
        ];
    }
}
