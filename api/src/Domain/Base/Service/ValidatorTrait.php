<?php

namespace App\Domain\Base\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Description of the common validation functionality.
 */
trait ValidatorTrait
{
    protected RepositoryInterface $repository;
    protected ValidationFactory $validationFactory;

    /**
     * Basic setup for constructor.
     *
     * @param RepositoryInterface $repository The repository
     * @param ValidationFactory $validationFactory The validation
     * @return void
     */
    protected function setUp(RepositoryInterface $repository, ValidationFactory $validationFactory): void
    {
        $this->repository = $repository;
        $this->validationFactory = $validationFactory;
    }

    /**
     * get the repository
     * @return RepositoryInterface repository
     */
    protected function getRepository() : RepositoryInterface
    {
        return $this->repository;
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
        if (!$this->repository->existsId($id)) {
            $result = new ValidationResult();
            if (is_null($errorMessage)) {
                $entityName = $this->repository->getEntityName();
                $errorMessage = "NotExist: $entityName $id is not valid.";
            }
            $result->addError("id", $errorMessage);
            throw new ValidationException("Please check your input", $result);
        }
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
    }

    /**
     * Validate update.
     *
     * @param string $id The entity id
     * @param array<string, mixed> $data The data
     *
     * @return void
     * @throws GenericException
     */
    public function validateUpdate(string $id, array $data): void
    {
        $this->validateExists($id);
        $this->validateEntity($data, newRecord: false);
    }

    /**
     * Validate Entity.
     * @param array<string, mixed> $data The data
     * @param Validator|null $validator Validation rules to be used
     * @param bool $newRecord Is it a create or update operation
     * @return void
     */
    protected function validateEntity(array $data, ?Validator $validator = null, bool $newRecord = true): void
    {
        if (is_null($validator)) {
            $validator = $this->createValidator();
        }

        $validationResult = $this->validationFactory->createValidationResult(
            $validator->validate($data, $newRecord)
        );

        if ($validationResult->fails()) {
            throw new ValidationException("Please check your input", $validationResult);
        }
    }

    /**
     * Create validator.
     *
     * @return Validator The validator
     */
    protected function createValidator(): Validator
    {
        return $this->validationFactory->createValidator();
    }

    /**
     * Does the value correspond to an enum entry (constant) of the specified type?
     * @param string|null $value Value to be checked
     * @param string $type Enum type
     * @return bool If true, enum entry (constant) exists
     */
    private static function isTypeOption(?string $value, string $type): bool
    {
        if (isset($value)) {
            $value = strtoupper($value);
            return defined("$type::$value");
        }
        return true;
    }
}
