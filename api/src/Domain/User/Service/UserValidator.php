<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\User\Repository\UserRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * User validation service.
 */
final class UserValidator
{
    use ValidatorTrait {
        ValidatorTrait::validateExists as private genericValidateExists;
    }

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(UserRepository $repository, ValidationFactory $validationFactory)
    {
        $this->setUp($repository, $validationFactory);
    }

    /**
     * Convert RepositoryInterface to UserRepository.
     * @return UserRepository UserRepository
     */
    protected function getRepository(): UserRepository
    {
        if ($this->repository instanceof UserRepository) {
            return $this->repository;
        }
    }

    /**
     * Validate create.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateLogin(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("username", "Empty: This field cannot be left empty")
                ->requirePresence("username", message: "Required: This field is required")
                ->email("username", message: "EMail: The username must be an email address.")
                ->notEmptyString("password", "Empty: This field cannot be left empty")
                ->requirePresence("password", message: "Required: This field is required")
        );

        $username = $data["username"];
        $password = $data["password"];
        if (!$this->getRepository()->checkPasswordForUsername($username, $password)) {
            $result = new ValidationResult();
            $result->addError("username or password", "NotExist: Username or password wrong.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate create.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     * @throws GenericException
     */
    public function validateCreate(array $data): void
    {
        $this->validateEntity($data);

        $username = $data["username"];
        if ($this->getRepository()->existsUsername($username)) {
            $result = new ValidationResult();
            $result->addError("username", "Exist: User $username already exists.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate if the entity exists.
     * @param string $id The entity id
     * @param string|null $errorMessage Custom error message
     * @return void
     * @throws GenericException
     */
    public function validateExists(string $id, ?string $errorMessage = null): void
    {
        $this->genericValidateExists($id, "NotExist: The logged-in user no longer exists.");
    }

    /**
     * Validate if the entity exists.
     * @param string $username The entity name
     * @param string|null $errorMessage Custom error message
     * @return void
     * @throws GenericException
     */
    public function validateUsernameExists(string $username, ?string $errorMessage = null): void
    {
        if (!$this->repository->exists(["username" => $username])) {
            $result = new ValidationResult();
            if (is_null($errorMessage)) {
                $entityName = $this->repository->getEntityName();
                $errorMessage = "NotExist: $entityName $username is not valid.";
            }
            $result->addError("username", $errorMessage);
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate password update.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     * @throws GenericException
     */
    public function validatePasswordUpdate(array $data): void
    {
        $userId = $this->repository->getAuthorisation()->id;
        $this->validateUpdate($userId, $data);

        $oldPassword = $data["oldPassword"];
        if (!$this->repository->checkEncryptTextForId($userId, $oldPassword)) {
            $result = new ValidationResult();
            $result->addError("password", "NotValid: The old password is wrong.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate password update.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     * @throws GenericException
     */
    public function validatePasswordReset(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("token", "Empty: This field cannot be left empty")
                ->requirePresence("token", message: "Required: This field is required")
                ->notEmptyString("password", "Empty: This field cannot be left empty")
                ->requirePresence("password", message: "Required: This field is required")
                ->notEmptyString("passwordConfirmation", "Empty: This field cannot be left empty")
                ->requirePresence("passwordConfirmation", message: "Required: This field is required")
                ->minLength("password", 8, "TooShort: Too short")
                ->maxLength("password", 255, "TooLong: Too long")
                ->regex(
                    "password",
                    "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).*$/",
                    "PatternMatch: Password must contain at least one lowercase and uppercase letter, a number and a special character."
                )
                ->equalToField("passwordConfirmation", "password", "Comparison: Password and confirmation do not match.")
        );
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
            ->notEmptyString("username", "Empty: This field cannot be left empty")
            ->requirePresence("username", "create", "Required: This field is required")
            ->email("username", message: "EMail: The username must be an email address.")
            ->notEmptyString("password", "Empty: This field cannot be left empty")
            ->requirePresence("password", message: "Required: This field is required")
            ->notEmptyString("passwordConfirmation", "Empty: This field cannot be left empty")
            ->requirePresence("passwordConfirmation", message: "Required: This field is required")
            ->minLength("password", 8, "TooShort: Too short")
            ->maxLength("password", 255, "TooLong: Too long")
            ->regex(
                "password",
                "/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).*$/",
                "PatternMatch: Password must contain at least one lowercase and uppercase letter, a number and a special character."
            )
            ->equalToField("passwordConfirmation", "password", "Comparison: Password and confirmation do not match.");
    }
}
