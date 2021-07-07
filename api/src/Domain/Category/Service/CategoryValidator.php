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
            ->notEmptyString("id")
            ->requirePresence("id", "update")
            ->notEmptyString("keywords")
            ->requirePresence("keywords", "create");
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
                ->notEmptyString("categoryId")
                ->requirePresence("categoryId")
                ->notEmptyArray("ideas")
                ->requirePresence("ideas")
        );

        $categoryId = $data["categoryId"];
        $ideas = $data["ideas"];

        if (!$this->repository->ideasAgreeWithCategory($categoryId, $ideas, $lookForConnected)) {
            $result = new ValidationResult();
            $message = "Not all ideas are valid idea keys or do not belong to the same topic as the category.";
            if ($lookForConnected) {
                $message = "Not all ideas are linked to the category.";
            }
            $result->addError(
                "ideas",
                $message
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
