<?php

namespace App\Domain\TaskParticipantIteration\Service;

use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ValidatorTrait;
use App\Domain\Idea\Type\IdeaSortOrder;
use App\Domain\TaskParticipantState\Repository\TaskParticipantStateRepository;
use App\Factory\ValidationFactory;
use Selective\Validation\Exception\ValidationException;
use Selective\Validation\ValidationResult;

/**
 * Selection validation service.
 */
class TaskParticipantIterationValidator
{
    use ValidatorTrait{
        ValidatorTrait::validateUpdate as private validateUpdateGeneric;
    }

    /**
     * The constructor.
     *
     * @param TaskParticipantStateRepository $repository The repository
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(TaskParticipantStateRepository $repository, ValidationFactory $validationFactory)
    {
        $this->setUp($repository, $validationFactory);
    }

    /**
     * Validate update.
     *
     * @param array<string, mixed> $data The data
     *
     * @return void
     */
    public function validateUpdate(array $data): void
    {
        $this->validateEntity($data, newRecord: false);
    }
}
