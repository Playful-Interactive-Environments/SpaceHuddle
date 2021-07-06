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
}
