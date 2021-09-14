<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Domain\Idea\Type\IdeaSortOrder;
use App\Domain\Idea\Type\IdeaState;
use App\Domain\Participant\Type\ParticipantState;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Idea validation service.
 */
class IdeaValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param IdeaRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(IdeaRepository $repository, ValidationFactory $validationFactory)
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
            ->requirePresence("keywords", "create", "Required: This field is required")
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, IdeaState::class);
                },
                "message" => "Type: Wrong idea state."
            ]);
    }

    /**
     * Topic validator.
     * @param array<string, mixed> $data The data
     * @param array $validStates Valid states
     * @return void
     */
    public function validateTopic(array $data, array $validStates): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("topicId", "Empty: This field cannot be left empty")
                ->requirePresence("topicId", message: "Required: This field is required")
                ->add("order", "custom", [
                    "rule" => function ($value) {
                        return self::isTypeOption($value, IdeaSortOrder::class);
                    },
                    "message" => "Type: Wrong sort order."
                ])
        );

        $topicId = $data["topicId"];
        $taskID = $this->repository->getTopicTask($topicId, $validStates);
        if (!isset($taskID)) {
            $result = new ValidationResult();
            $result->addError("topicId", "NotValid: Topic has no active BRAINSTORMING task.");
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
        $this->validateEntity(
            $data
        );

        if (array_key_exists("taskId", $data)){
            $taskId = $data["taskId"];
            $this->validateTaskType($taskId);
        }

        $this->validateIdeeExists($data);
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
                ->add("order", "custom", [
                    "rule" => function ($value) {
                        $valueList = IdeaRepository::convertToList($value);
                        foreach ($valueList as $value) {
                            if (!self::isTypeOption($value, IdeaSortOrder::class))
                                return false;
                        }
                        return true;
                    },
                    "message" => "Type: Wrong sort order."
                ])
        );

        $taskId = $data["taskId"];
        $this->validateTaskType($taskId);
    }

    /**
     * Validate task type.
     * @param string $taskId Task Id to be checked.
     * @return void
     */
    private function validateTaskType(string $taskId): void
    {
        if (!$this->repository->taskHasCorrectTaskType($taskId)) {
            $result = new ValidationResult();
            $result->addError(
                "taskId",
                "NotValid: The specified task has the wrong type. A BRAINSTORMING task is expected."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate if the user has already submitted the idea.
     * @param array $data The data to be inserted
     * @return void
     */
    private function validateIdeeExists(array $data): void
    {
        if (!$this->repository->isNew((object)$data)) {
            $result = new ValidationResult();
            $result->addError(
                "idea",
                "Exist: The idea has already been submitted."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
