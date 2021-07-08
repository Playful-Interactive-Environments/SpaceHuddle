<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Selection\Repository\VoteRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

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
}
