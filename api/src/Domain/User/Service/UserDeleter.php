<?php

namespace App\Domain\User\Service;

use App\Domain\User\Repository\UserRepository;

/**
 * Service.
 */
final class UserDeleter
{
    private UserRepository $repository;

    private UserValidator $userValidator;

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     */
    public function __construct(
        UserRepository $repository,
        UserValidator $userValidator,
    )
    {
        $this->repository = $repository;
        $this->userValidator = $userValidator;
    }

    /**
     * Delete user.
     *
     * @param int $userId The user id
     *
     * @return void
     */
    public function deleteUser(string $userId): mixed
    {
        // Input validation
        $this->userValidator->validateUserExists($userId);

        $this->repository->deleteUserById($userId);

        return [
                "state" => "Success",
                "message" => "user was successfully deleted."
            ];
    }
}
