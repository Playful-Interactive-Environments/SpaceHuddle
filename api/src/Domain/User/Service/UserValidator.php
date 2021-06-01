<?php

namespace App\Domain\User\Service;

use App\Domain\User\Repository\UserRepository;
use App\Domain\User\Type\UserRoleType;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;

/**
 * Service.
 */
final class UserValidator
{
    private UserRepository $repository;

    private ValidationFactory $validationFactory;

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(UserRepository $repository, ValidationFactory $validationFactory)
    {
        $this->repository = $repository;
        $this->validationFactory = $validationFactory;
    }

    /**
     * Validate create.
     *
     * @param int $userId The user id
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateUserCreate(array $data): void
    {
        $this->validateUser($data);

        $username = $data["username"];
        if ($this->repository->existsUsername($username)) {
            throw new ValidationException("User $username already exists");
        }
    }

    /**
     * Validate update.
     *
     * @param int $userId The user id
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateUserUpdate(int $userId, array $data): void
    {
        if (!$this->repository->existsUserId($userId)) {
            throw new ValidationException(sprintf('User not found: %s', $userId));
        }

        $this->validateUser($data);
    }

    /**
     * Validate new user.
     *
     * @param array<mixed> $data The data
     *
     * @throws ValidationException
     *
     * @return void
     */
    public function validateUser(array $data): void
    {
        $validator = $this->createValidator();

        $validationResult = $this->validationFactory->createValidationResult(
            $validator->validate($data)
        );

        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
    }

    /**
     * Create validator.
     *
     * @return Validator The validator
     */
    private function createValidator(): Validator
    {
        $validator = $this->validationFactory->createValidator();

        return $validator
            ->notEmptyString('username')
            ->requirePresence('username', 'create')
            ->notEmptyString('password')
            ->requirePresence('password')
            ->notEmptyString('passwordConfirmation')
            ->requirePresence('passwordConfirmation')
            ->minLength('password', 8, 'Too short')
            ->maxLength('password', 40, 'Too long')
            //->compareFields('password', 'passwordConfirmation', 'COMPARE_EQUAL')
            ->add('password', 'custom', [
                'rule' => function ($value, $context) {
                    if (array_key_exists("passwordConfirmation", $context["data"]))
                        return ($value == $context["data"]["passwordConfirmation"]);
                    return false;
                },
                'message' => 'Password and confirmation do not match.'
            ]);
    }
}
