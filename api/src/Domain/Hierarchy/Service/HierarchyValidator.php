<?php

namespace App\Domain\Hierarchy\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Hierarchy\Repository\HierarchyRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Hierarchy validation service.
 */
class HierarchyValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param HierarchyRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(HierarchyRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("id", "Empty: This field cannot be left empty")
            ->requirePresence("id", "update", "Required: This field is required")
            ->notEmptyString("keywords", "Empty: This field cannot be left empty")
            ->requirePresence("keywords", "create", "Required: This field is required");
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
        $this->validateEntity(
            $data
        );

        if (array_key_exists("taskId", $data)){
            $taskId = $data["taskId"];
        }
    }

    /**
     * Validate read.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateRead(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("taskId", "Empty: This field cannot be left empty")
                ->requirePresence("taskId", message: "Required: This field is required")
        );
    }
}
