<?php

namespace App\Domain\Resource\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Resource\Repository\ResourceRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Selection validation service.
 */
class ResourceValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param ResourceRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(ResourceRepository $repository, ValidationFactory $validationFactory)
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
