<?php

namespace App\Domain\User\Service;

use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
use App\Data\AuthorisationRoleType;
use App\Domain\Base\Service\AbstractService;
use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Type\UserRoleType;
use App\Factory\LoggerFactory;

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
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->authorisationPermissionList = [AuthorisationRoleType::USER];
        $this->entityPermissionList = [
            UserRoleType::MODERATOR,
            UserRoleType::FACILITATOR
        ];
    }

    /**
     * Functionality of the change password for the logged in user service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);

        // Input validation
        $this->validator->validatePasswordUpdate($authorisation->id, $data);

        // Validation was successfully
        $user = (object)$data;
        $user->id = $authorisation->id;

        $this->transaction->begin();
        // Update the user
        $result = $this->repository->updatePassword($user);
        $this->transaction->commit();

        // Logging
        $this->logger->info("The password was successfully updated: $authorisation->id");

        return [
            "state" => "Success",
            "message" => "The password was successfully updated."
        ];
    }
}
