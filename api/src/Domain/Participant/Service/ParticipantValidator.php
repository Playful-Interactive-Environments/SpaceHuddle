<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Domain\Participant\Type\ParticipantState;
use App\Factory\ValidationFactory;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Service.
 */
class ParticipantValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param ParticipantRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(ParticipantRepository $repository, ValidationFactory $validationFactory)
    {
        $this->setUp($repository, $validationFactory);
    }

    /**
     * Validate create.
     *
     * @param int $userId The user id
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateConnect(array $data): void
    {
        $this->validateEntity($data,
            $this->validationFactory->createValidator()
                ->notEmptyString("sessionKey")
                ->requirePresence("sessionKey")
                ->notEmptyString("ip")
                ->requirePresence("ip")
                ->add("state", "custom", [
                    "rule" => function ($value) {
                        return self::isTypeOption($value, ParticipantState::class);
                    },
                    "message" => "Wrong participant state."
                ])
        );

        $sessionKey = $data["sessionKey"];
        if (!$this->getRepository()->checkSessionKey($sessionKey)) {
            $result = new ValidationResult();
            $result->addError("sessionKey", "sessionKey wrong.");
            throw new ValidationException("Please check your input", $result);
        }
    }
}
