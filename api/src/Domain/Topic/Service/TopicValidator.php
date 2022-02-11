<?php

namespace App\Domain\Topic\Service;

use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Topic\Repository\TopicRepository;
use App\Domain\Topic\Type\ExportType;
use App\Factory\ValidationFactory;
use Cake\Validation\Validator;

/**
 * Topic validation service.
 */
class TopicValidator
{
    use ValidatorTrait;

    /**
     * The constructor.
     *
     * @param TopicRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(TopicRepository $repository, ValidationFactory $validationFactory)
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
     * Export validator.
     * @param array $data Data to be verified.
     * @return void
     */
    public function validateExport(array $data): void
    {
        $this->validateEntity(
            $data,
            $this->validationFactory->createValidator()
                ->notEmptyString("id", "Empty: This field cannot be left empty")
                ->requirePresence("id", message: "Required: This field is required")
                ->notEmptyString("exportType", "Empty: This field cannot be left empty")
                ->requirePresence("exportType", message: "Required: This field is required")
                ->add("exportType", "custom", [
                        "rule" => function ($value) {
                            return self::isTypeOption($value, ExportType::class);
                        },
                        "message" => "Type: Wrong export type."
                    ])
        );
    }
}
