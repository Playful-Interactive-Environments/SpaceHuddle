<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Session validation Service.
 */
final class SessionValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param SessionRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SessionRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("title")
            ->requirePresence("title", "create");
    }
}
