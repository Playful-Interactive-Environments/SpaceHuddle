<?php

namespace App\Domain\Selection\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Selection\Repository\SelectionRepository;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Selection validation service.
 */
class SelectionValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param SelectionRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(SelectionRepository $repository, ValidationFactory $validationFactory)
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
            ->notEmptyString("name")
            ->requirePresence("name", "create");
    }

    /**
     * Ideas validator.
     * @param array $data Data to be verified.
     * @param bool $lookForConnected If true, only ideas already associated with the category are valid.
     * @return void
     */
    public function validateIdeas(array $data, bool $lookForConnected = false): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("selectionId")
                ->requirePresence("selectionId")
                ->notEmptyArray("ideas")
                ->requirePresence("ideas")
        );

        $selectionId = $data["selectionId"];
        $ideas = $data["ideas"];

        if (!$this->repository->ideasAgreeWithSelection($selectionId, $ideas, $lookForConnected)) {
            $result = new ValidationResult();
            $message = "Not all ideas are valid idea keys or do not belong to the same topic as the selection.";
            if ($lookForConnected) {
                $message = "Not all ideas are linked to the selection.";
            }
            $result->addError(
                "ideas",
                $message
            );
            throw new ValidationException("Please check your input", $result);
        }
    }
}
