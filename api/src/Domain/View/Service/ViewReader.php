<?php

namespace App\Domain\View\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all categories for one task.
 */
class ViewReader
{
    use ServiceReaderTrait;
    use ViewServiceTrait;

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
