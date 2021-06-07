<?php

namespace App\Domain\Base;

use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Description of the common validation functionality.
 */
abstract class AbstractValidator
{
    protected AbstractRepository $repository;
    protected ValidationFactory $validationFactory;

    /**
     * The constructor.
     *
     * @param AbstractRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(AbstractRepository $repository, ValidationFactory $validationFactory)
    {
        $this->repository = $repository;
        $this->validationFactory = $validationFactory;
    }

    /**
     * get the repository
     * @return AbstractRepository repository
     */
    protected function getRepository() : AbstractRepository {
        return $this->repository;
    }

    /**
     * Validate if the entity exists.
     *
     * @param string $id The entity id
     *
     * @return void
     */
    public function validateExists(string $id, ?string $errorMessage = null): void
    {
        if (!$this->repository->existsId($id)) {
            $result = new ValidationResult();
            if (is_null($errorMessage))
            {
                $entityName = $this->repository->getEntityName();
                $errorMessage = "$entityName $id is not valid.";
            }
            $result->addError("id", $errorMessage);
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
    public function validateCreate(array $data): void
    {
        $this->validateEntity($data);
    }

    /**
     * Validate update.
     *
     * @param string $id The entity id
     * @param array<mixed> $data The data
     *
     * @return void
     */
    public function validateUpdate(string $id, array $data): void
    {
        $this->validateExists($id);
        $this->validateEntity($data, newRecord: false);
    }

    /**
     * Validate Entity.
     *
     * @param array<mixed> $data The data
     *
     * @throws ValidationException
     *
     * @return void
     */
    protected function validateEntity(array $data, ?Validator $validator = null, bool $newRecord = true): void
    {
        if (is_null($validator))
            $validator = $this->createValidator();

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
        $validator = $this->validationFactory->createValidator();

        return $validator;
    }
}
