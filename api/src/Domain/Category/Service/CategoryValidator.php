<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Category\Repository\CategoryRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Category validation service.
 */
class CategoryValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param CategoryRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(CategoryRepository $repository, ValidationFactory $validationFactory)
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
                ->requirePresence("topicId", "Required: This field is required")
        );

        $topicId = $data["topicId"];
        $taskID = $this->repository->getTopicTask($topicId, $validStates);
        if (!isset($taskID)) {
            $result = new ValidationResult();
            $result->addError("topicId", "Topic has no active CATEGORISATION task.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Ideas validator.
     * @param array $data Data to be verified.
     * @param bool $lookForConnected If true, only ideas already associated with the category are valid.
     * @return void
     */
    public function validateIdeas(array $data, bool $lookForConnected = false): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("categoryId", "Empty: This field cannot be left empty")
                ->requirePresence("categoryId", "Required: This field is required")
                ->notEmptyArray("ideas", "Empty: This field cannot be left empty")
                ->requirePresence("ideas", "Required: This field is required")
        );

        $categoryId = $data["categoryId"];
        $ideas = $data["ideas"];

        if (!$this->repository->ideasAgreeWithCategory($categoryId, $ideas, $lookForConnected)) {
            $result = new ValidationResult();
            $message = "NotValid: Not all ideas are valid idea keys or do not belong to the same topic as the category.";
            if ($lookForConnected) {
                $message = "NotValid: Not all ideas are linked to the category.";
            }
            $result->addError(
                "ideas",
                $message
            );
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
                ->requirePresence("taskId", "Required: This field is required")
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
                "NotValid: The specified task has the wrong type. A CATEGORISATION task is expected."
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
