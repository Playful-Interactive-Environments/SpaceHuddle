<?php

namespace App\Domain\Vote\Service;

use App\Domain\Base\Service\ServiceReaderTrait;

/**
 * Service to read all votes for one topic
 */
class VoteReader
{
    use ServiceReaderTrait;
    use VoteServiceTrait;

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
