<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Idea\Repository\IdeaRepository;
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
            ->notEmptyString("id")
            ->requirePresence("id", "update")
            ->notEmptyString("keywords")
            ->requirePresence("keywords", "create")
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, IdeaState::class);
                },
                "message" => "Wrong idea state."
            ]);
    }

    /**
     * Topic validator.
     * @param string $topicId Topic ID
     * @param array $validStates Valid states
     * @return void
     */
    public function validateTopic(string $topicId, array $validStates): void
    {
        $taskID = $this->repository->getTopicTask($topicId, $validStates);
        if (!isset($taskID)) {
            $result = new ValidationResult();
            $result->addError("topicId", "Topic has no active BRAINSTORMING task.");
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
                ->notEmptyString("taskId")
                ->requirePresence("taskId")
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
                "The specified task has the wrong type. A BRAINSTORMING task is expected."
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
                "The idea has already been submitted."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
