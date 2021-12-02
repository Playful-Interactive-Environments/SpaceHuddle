<?php

namespace App\Domain\View\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\View\Repository\ViewRepository;
use App\Factory\ValidationFactory;

/**
 * View validation service.
 */
class ViewValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param ViewRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(ViewRepository $repository, ValidationFactory $validationFactory)
    {
        $this->setUp($repository, $validationFactory);
    }

    /**
     * Validate read.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateRead(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("topicId", "Empty: This field cannot be left empty")
                ->requirePresence("topicId", message: "Required: This field is required")
        );
    }

    /**
     * Validate read.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateDetailRead(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("type", "Empty: This field cannot be left empty")
                ->requirePresence("type", message: "Required: This field is required")
                ->notEmptyString("typeId", "Empty: This field cannot be left empty")
                ->requirePresence("typeId", message: "Required: This field is required")
        );
    }
}
