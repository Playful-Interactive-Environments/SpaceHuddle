<?php

namespace App\Domain\User\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service.
 */
final class UserReader
{
    use ServiceReaderTrait;
    use UserServiceTrait;

    /**
     * Validates whether the transferred data is suitable for the service.
     * @param array $data Data to be verified.
     * @return void
     */
    protected function serviceValidation(array $data): void
    {
        $this->validator->validateAdmin($data);
    }
}
