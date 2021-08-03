<?php

namespace App\Domain\Task\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Task validation service.
 */
class TaskValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param TaskRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(TaskRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("taskType", "Empty: This field cannot be left empty")
            ->requirePresence("taskType", "create", "Required: This field is required")
            ->notEmptyString("name", "Empty: This field cannot be left empty")
            ->requirePresence("name", "create", "Required: This field is required")
            ->add("taskType", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, TaskType::class);
                },
                "message" => "Type: Wrong task type."
            ])
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, TaskState::class);
                },
                "message" => "Type: Wrong task state."
            ]);
    }

    /**
     * State update validator.
     * @param array $data Data to be verified.
     * @return void
     */
    public function validateStateUpdate(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("taskId", "Empty: This field cannot be left empty")
                ->requirePresence("taskId", message: "Required: This field is required")
                ->notEmptyArray("state", "Empty: This field cannot be left empty")
                ->requirePresence("state", message: "Required: This field is required")
                ->add("state", "custom", [
                    "rule" => function ($value) {
                        return self::isTypeOption($value, TaskState::class);
                    },
                    "message" => "Type: Wrong task state."
                ])
        );
    }
}
