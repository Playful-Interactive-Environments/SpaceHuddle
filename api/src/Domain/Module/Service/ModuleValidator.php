<?php

namespace App\Domain\Module\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Module\Repository\ModuleRepository;
use App\Domain\Module\Type\ModuleState;
use App\Domain\Task\Type\TaskType;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Module validation service.
 */
class ModuleValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param ModuleRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(ModuleRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("name", "Empty: This field cannot be left empty")
            ->requirePresence("name", "create", "Required: This field is required")
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, ModuleState::class);
                },
                "message" => "Type: Wrong module state."
            ]);
    }

    /**
     * State update validator.
     * @param array $data Data to be verified.
     * @return void
     */
    public function usedValidator(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("taskType", "Empty: This field cannot be left empty")
                ->requirePresence("taskType", "create", "Required: This field is required")
                ->add("taskType", "custom", [
                    "rule" => function ($value) {
                        return self::isTypeOption($value, TaskType::class);
                    },
                    "message" => "Type: Wrong task type."
                ])
        );
    }
}
