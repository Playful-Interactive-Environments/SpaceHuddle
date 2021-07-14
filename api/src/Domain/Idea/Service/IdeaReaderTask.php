<?php

namespace App\Domain\Idea\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all ideas for one task.
 */
class IdeaReaderTask
{
    use ServiceReaderTrait;
    use IdeaServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateRead($data);
    }
}
