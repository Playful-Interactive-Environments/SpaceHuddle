<?php

namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;
use App\Database\TransactionInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Description of the common service functionality.
 */
trait BaseBodyServiceTrait
{
    use BaseUrlServiceTrait;

    /**
     * Functionality of the service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $this->repository->setAuthorisation($authorisation);

        $data = array_merge($bodyData, $urlData);

        // validation
        $this->serviceValidation($data);

        // Executes the repository instructions assigned to the service.
        return $this->serviceExecution($data);
    }
}
