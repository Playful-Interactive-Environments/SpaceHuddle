<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Topic\Repository\TopicRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Topic validation service.
 */
class TopicValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param TopicRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(TopicRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("title", "Empty: This field cannot be left empty")
            ->requirePresence("title", "create", "Required: This field is required");
    }
}
