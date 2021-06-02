<?php

namespace App\Domain\Session\Service;

use App\Domain\Session\Repository\SessionRepository;
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
final class SessionValidator extends AbstractValidator
{
    private SessionRepository $repository;

    /**
     * The constructor.
     *
     * @param UserRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SessionRepository $repository, ValidationFactory $validationFactory)
    {
        parent::__construct($validationFactory);
        $this->repository = $repository;
    }

    /**
     * Validate create.
     *
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateSessionCreate(array $data): void
    {
        $this->validateEntity($data);
    }

    /**
     * Validate if the use exists.
     *
     * @param string $userId The user id
     *
     * @return void
     */
    public function validateSessionExists(string $sessionId): void
    {
        if (!$this->repository->existsSessionId($sessionId)) {
            $result = new ValidationResult();
            $result->addError("sessionId", "Session $sessionId is not valid.");
            throw new ValidationException("Please check your login", $result);
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
    public function validateSessionUpdate(string $userId, array $data): void
    {
        $this->validateSessionExists($userId);
        $this->validateEntity($data, newRecord: false);
    }

    /**
     * Validate password update.
     *
     * @param string $userId The user id
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validatePasswordUpdate(string $userId, array $data): void
    {
        $this->validateSessionUpdate($userId, $data);
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
            ->notEmptyString("id")
            ->requirePresence("id", "update")
            ->notEmptyString("title")
            ->requirePresence("title", "create");
    }
}
