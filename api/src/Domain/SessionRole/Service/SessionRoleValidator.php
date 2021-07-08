<?php

namespace App\Domain\SessionRole\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Selection\Repository\SessionRoleRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Selection validation service.
 */
class SessionRoleValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param SessionRoleRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SessionRoleRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("username")
            ->requirePresence("username")
            ->notEmptyString("role")
            ->requirePresence("role");
    }
}
