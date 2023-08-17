<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Vote\Repository\VoteRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Selection validation service.
 */
class VoteValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param VoteRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(VoteRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("ideaId", "Empty: This field cannot be left empty")
            ->requirePresence("ideaId", "create", "Required: This field is required");
    }

    /**
     * Validate create.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     * @throws GenericException
     */
    public function validateCreate(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("id", "Empty: This field cannot be left empty")
                ->requirePresence("id", "update", "Required: This field is required")
                ->notEmptyString("ideaId", "Empty: This field cannot be left empty")
                ->requirePresence("ideaId", message: "Required: This field is required")
                ->notEmptyString("taskId", "Empty: This field cannot be left empty")
                ->requirePresence("taskId", message: "Required: This field is required")
        );

        $taskId = $data["taskId"];
        $ideaId = $data["ideaId"];

        $this->validateTaskType($taskId);
        $this->validateTaskState($taskId);

        if (!$this->repository->ideaAgreeWithTask($taskId, $ideaId)) {
            $result = new ValidationResult();
            $result->addError(
                "ideaId",
                "NotValid: IdeaId is not a valid idea keys or do not belong to the same topic as the task."
            );
            throw new ValidationException("Please check your input", $result);
        }

        if (!isset($data["id"])) {
            $this->votingExists($taskId, $ideaId);
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

        $taskId = $data["taskId"];
        $this->validateTaskType($taskId);
    }

    /**
     * Validate read.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateParameter(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("parameter", "Empty: This field cannot be left empty")
                ->requirePresence("parameter", message: "Required: This field is required")
        );
    }

    /**
     * Validate read.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateHierarchyRead(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("parentId", "Empty: This field cannot be left empty")
                ->requirePresence("parentId", message: "Required: This field is required")
        );
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
                "NotValid: The specified task has the wrong type. A VOTING task is expected."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate task state.
     * @param string $taskId Task Id to be checked.
     * @throws GenericException
     */
    private function validateTaskState(string $taskId): void
    {
        $task = $this->repository->getParentRepository()->get(["task.id" => $taskId]);
        if (!isset($task)) {
            $result = new ValidationResult();
            $result->addError("taskId", "NotValid: Task is not active.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate voting already done.
     * @param string $taskId Task Id to be checked.
     * @param string $ideaId Idea Id to be checked.
     */
    private function votingExists(string $taskId, string $ideaId): void
    {
        if ($this->repository->votingExists($taskId, $ideaId)) {
            $result = new ValidationResult();
            $result->addError("taskId, ideaId", "NotValid: Voting already done.");
            throw new ValidationException("Please check your input", $result);
        }
    }
}
