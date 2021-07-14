<?php

namespace App\Domain\Category\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all categories for one task.
 */
class CategoryReaderTask
{
    use ServiceReaderTrait;
    use CategoryServiceTrait;

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
