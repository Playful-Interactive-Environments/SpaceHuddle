<?php

namespace App\Domain\User\Service;

use App\Domain\User\Repository\UserRepository;
use App\Domain\Service\AbstractValidator;
use App\Domain\User\Type\UserRoleType;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Service.
 */
final class UserValidator extends AbstractValidator
{
    private UserRepository $repository;

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(UserRepository $repository, ValidationFactory $validationFactory)
    {
        parent::__construct($validationFactory);
        $this->repository = $repository;
    }

    /**
     * Validate create.
     *
     * @param int $userId The user id
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateLogin(array $data): void
    {
        $this->validateEntity($data,
            $this->validationFactory->createValidator()
                ->notEmptyString("username")
                ->requirePresence("username")
                ->notEmptyString("password")
                ->requirePresence("password")
        );

        $username = $data["username"];
        $password = $data["password"];
        if (!$this->repository->checkPassword($username, $password)) {
            $result = new ValidationResult();
            $result->addError("username or password", "Username or password wrong.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate create.
     *
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateUserCreate(array $data): void
    {
        $this->validateEntity($data);

        $username = $data["username"];
        if ($this->repository->existsUsername($username)) {
            $result = new ValidationResult();
            $result->addError("username", "User $username already exists.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate update.
     *
     * @param string $userId The user id
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateUserUpdate(string $userId, array $data): void
    {
        if (!$this->repository->existsUserId($userId)) {
            $result = new ValidationResult();
            $result->addError("userId", "The logged-in user no longer exists.");
            throw new ValidationException("Please check your login");
        }

        $this->validateEntity($data, newRecord: false);
    }

    /**
     * Create validator.
     *
     * @return Validator The validator
     */
    protected function createValidator(): Validator
    {
        $validator = $this->validationFactory->createValidator();

        return $validator
            ->notEmptyString("username")
            ->requirePresence("username", "create")
            ->notEmptyString("password")
            ->requirePresence("password")
            ->notEmptyString("passwordConfirmation")
            ->requirePresence("passwordConfirmation")
            ->minLength("password", 8, "Too short")
            ->maxLength("password", 40, "Too long")
            ->equalToField("passwordConfirmation", "password", "Password and confirmation do not match.");
    }
}
