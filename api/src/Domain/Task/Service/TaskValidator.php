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
            ->notEmptyString("id")
            ->requirePresence("id", "update")
            ->notEmptyString("taskType")
            ->requirePresence("taskType", "create")
            ->notEmptyString("name")
            ->requirePresence("name", "create")
            ->add("taskType", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, TaskType::class);
                },
                "message" => "Wrong task type."
            ])
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, TaskState::class);
                },
                "message" => "Wrong task state."
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
                ->notEmptyString("taskId")
                ->requirePresence("taskId")
                ->notEmptyArray("state")
                ->requirePresence("state")
                ->add("state", "custom", [
                    "rule" => function ($value) {
                        return self::isTypeOption($value, TaskState::class);
                    },
                    "message" => "Wrong task state."
                ])
        );
    }
}
