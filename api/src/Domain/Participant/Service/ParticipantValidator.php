<?php

namespace App\Domain\Participant\Service;

use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Participant\Repository\ParticipantRepository;
use App\Domain\Participant\Type\ParticipantState;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Participant validation service.
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
     * get the repository
     * @return ParticipantRepository repository
     */
    protected function getRepository() : ParticipantRepository
    {
        return $this->repository;
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
            ->notEmptyString("sessionKey")
            ->requirePresence("sessionKey", "create")
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, ParticipantState::class);
                },
                "message" => "Wrong participant state."
            ]);
    }

    /**
     * Validate connect.
     *
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
        );

        $sessionKey = $data["sessionKey"];
        if (!$this->getRepository()->checkSessionKey($sessionKey)) {
            $result = new ValidationResult();
            $result->addError("sessionKey", "sessionKey wrong.");
            throw new ValidationException("Please check your input", $result);
        }
    }

    /**
     * Validate reconnect.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateReconnect(array $data): void
    {
        $this->validateEntity($data,
            $this->validationFactory->createValidator()
                ->notEmptyString("browserKey")
                ->requirePresence("browserKey")
        );

        $browserKey = $data["browserKey"];
        if (!$this->getRepository()->checkBrowserKey($browserKey)) {
            $result = new ValidationResult();
            $result->addError("browserKey", "browserKey wrong.");
            throw new ValidationException("Please check your input", $result);
        }
    }
}
