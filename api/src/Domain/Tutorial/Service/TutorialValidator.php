<?php

namespace App\Domain\Tutorial\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Task\Type\TaskState;
use App\Domain\Task\Type\TaskType;
use App\Domain\Tutorial\Repository\TutorialRepository;
use App\Domain\View\Repository\ViewRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * View validation service.
 */
class TutorialValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param TutorialRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(TutorialRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("step", "Empty: This field cannot be left empty")
            ->requirePresence("step", "update", "Required: This field is required");
    }
}
