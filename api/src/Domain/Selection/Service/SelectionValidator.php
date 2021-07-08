<?php

namespace App\Domain\Selection\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Selection validation service.
 */
class SelectionValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param SelectionRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SelectionRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("name")
            ->requirePresence("name", "create");
    }
}
