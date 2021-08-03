<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Session\Type\SessionRoleType;
use App\Domain\SessionRole\Repository\SessionRoleRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Selection validation service.
 */
class SessionRoleValidator
{
    use ValidatorTrait{
        ValidatorTrait::validateUpdate as private validateUpdateGeneric;
    }

    /**
     * The constructor.
     *
     * @param SessionRoleRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SessionRoleRepository $repository, ValidationFactory $validationFactory)
    {
        $this->setUp($repository, $validationFactory);
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
            ->requirePresence("username", message: "Required: This field is required")
            ->notEmptyString("role", "Empty: This field cannot be left empty")
            ->requirePresence("role", message: "Required: This field is required")
            ->add("role", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, SessionRoleType::class);
                },
                "message" => "Type: Wrong session role type."
            ]);
    }

    /**
     * Validate create.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateCreate(array $data): void
    {
        $this->validateEntity($data);

        $username = $data["username"];
        $this->validateUsername($username);
    }

    /**
     * Validate update.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateUpdate(array $data): void
    {
        $this->validateEntity($data, newRecord: false);

        $username = $data["username"];
        $this->validateUsername($username);
        $this->validateOwnUser($username);
    }

    /**
     * Validate update.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateDelete(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("username", "Empty: This field cannot be left empty")
                ->requirePresence("username", message: "Required: This field is required"),
            newRecord: false
        );

        $username = $data["username"];
        $this->validateUsername($username);
        $this->validateOwnUser($username);
    }



    /**
     * Validate if username exits.
     * @param string $username Username to be checked.
     * @return void
     */
    private function validateUsername(string $username): void
    {
        $userId = $this->repository->getUserId($username);
        if (is_null($userId)) {
            $result = new ValidationResult();
            $result->addError(
                "username",
                "NotExist: Username not exists."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }



    /**
     * Validate if username is own user.
     * @param string $username Username to be checked.
     * @return void
     */
    private function validateOwnUser(string $username): void
    {
        if ($this->repository->isOwnUsername($username)) {
            $result = new ValidationResult();
            $result->addError(
                "username",
                "NoPermission: One's own role cannot be changed."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
