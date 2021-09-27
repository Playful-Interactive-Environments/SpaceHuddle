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
            ->notEmptyString("id", "Empty: This field cannot be left empty")
            ->requirePresence("id", "update", "Required: This field is required")
            ->notEmptyString("sessionKey", "Empty: This field cannot be left empty")
            ->requirePresence("sessionKey", "create", "Required: This field is required")
            ->add("state", "custom", [
                "rule" => function ($value) {
                    return self::isTypeOption($value, ParticipantState::class);
                },
                "message" => "Type: Wrong participant state."
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
                ->notEmptyString("sessionKey", "Empty: This field cannot be left empty")
                ->requirePresence("sessionKey", message: "Required: This field is required")
        );

        $sessionKey = $data["sessionKey"];
        if (!$this->getRepository()->checkSessionKey($sessionKey)) {
            $result = new ValidationResult();
            $result->addError("sessionKey", "NotExist: sessionKey wrong");
            throw new ValidationException("Please check your input", $result);
        }
        if (!$this->getRepository()->checkExpirationDateForConnect($sessionKey)) {
            $result = new ValidationResult();
            $result->addError("sessionKey", "Expired: the session has expired");
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
                ->notEmptyString("browserKey", "Empty: This field cannot be left empty")
                ->requirePresence("browserKey", message: "Required: This field is required")
        );

        $browserKey = $data["browserKey"];
        if (!$this->getRepository()->checkBrowserKey($browserKey)) {
            $result = new ValidationResult();
            $result->addError("browserKey", "NotExist: browserKey wrong");
            throw new ValidationException("Please check your input", $result);
        }
        if (!$this->getRepository()->checkExpirationDateForReconnect($browserKey)) {
            $result = new ValidationResult();
            $result->addError("browserKey", "Expired: the session has expired");
            throw new ValidationException("Please check your input", $result);
        }
    }
}
