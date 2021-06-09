<?php

namespace App\Domain\Session\Service;

use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Base\Service\AbstractValidator;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Service.
 */
final class SessionValidator extends AbstractValidator
{
    /**
     * The constructor.
     *
     * @param SessionRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SessionRepository $repository, ValidationFactory $validationFactory)
    {
        parent::__construct($repository, $validationFactory);
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
