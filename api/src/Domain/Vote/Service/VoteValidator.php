<?php

namespace App\Domain\Vote\Service;

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
            ->notEmptyString("id")
            ->requirePresence("id", "update")
            ->notEmptyString("ideaId")
            ->requirePresence("ideaId", "create");
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
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("ideaId")
                ->requirePresence("ideaId")
                ->notEmptyString("taskId")
                ->requirePresence("taskId")
        );

        $taskId = $data["taskId"];
        $ideaId = $data["ideaId"];

        $this->validateTaskType($taskId);

        if (!$this->repository->ideaAgreeWithTask($taskId, $ideaId)) {
            $result = new ValidationResult();
            $result->addError(
                "ideaId",
                "IdeaId is not a valid idea keys or do not belong to the same topic as the task."
            );
            throw new ValidationException("Please check your input", $result);
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
                "The specified task has the wrong type. A VOTING task is expected."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
