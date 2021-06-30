<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Idea\Repository\IdeaRepository;
use App\Domain\Idea\Type\IdeaState;
use App\Domain\Participant\Type\ParticipantState;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Idea validation service.
 */
class IdeaValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param IdeaRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(IdeaRepository $repository, ValidationFactory $validationFactory)
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
            ->requirePresence("keywords", "create")
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, IdeaState::class);
                },
                "message" => "Wrong idea state."
            ]);
    }
}
