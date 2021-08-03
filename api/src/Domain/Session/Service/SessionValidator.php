<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

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
            ->notEmptyString("id", "Empty: This field cannot be left empty")
            ->requirePresence("id", "update", "Required: This field is required")
            ->notEmptyString("title", "Empty: This field cannot be left empty")
            ->requirePresence("title", "create", "Required: This field is required");
    }

    /**
     * State update validator.
     * @param array $data Data to be verified.
     * @return void
     */
    public function validatePublicScreenUpdate(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("sessionId", "Empty: This field cannot be left empty")
                ->requirePresence("sessionId", message: "Required: This field is required")
        );

        $sessionId = $data["sessionId"];
        $taskId = $data["taskId"];
        if (isset($taskId)) {
            $moduleId = $this->repository->getPossiblePublicScreenModule($sessionId, $taskId);

            if (is_null($moduleId)) {
                $result = new ValidationResult();
                $result->addError(
                    "taskId",
                    "NotValid: The task does not belong to the session or has no module and can not be set for public screen."
                );
                throw new ValidationException("Please check your input", $result);
            }
        }
    }
}
