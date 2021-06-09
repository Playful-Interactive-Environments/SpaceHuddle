<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Data\AbstractData;
use App\Domain\Base\Data\AuthorisationData;
use App\Domain\Base\Data\AuthorisationRole;
use App\Domain\Base\Service\AbstractService;
use App\Domain\Base\Service\ServiceUpdater;
use App\Domain\User\Data\UserData;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Service.
 */
final class UserUpdater extends AbstractService
{
    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param UserValidator $validator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $loggerFactory);
        $this->permission = [AuthorisationRole::USER];
    }

    /**
     * Functionality of the change password for the logged in user service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws \App\Domain\Base\Data\AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);

        // Input validation
        $this->validator->validatePasswordUpdate($authorisation->id, $data);

        // Validation was successfully
        $user = (object)$data;
        $user->id = $authorisation->id;

        // Update the user
        $result = $this->repository->updatePassword($user);

        // Logging
        $this->logger->info("The password was successfully updated: $authorisation->id");

        return [
            "state" => "Success",
            "message" => "The password was successfully updated."
        ];
    }
}
