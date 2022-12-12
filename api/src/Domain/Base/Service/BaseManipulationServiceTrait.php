<?php

namespace App\Domain\Base\Service;

use App\Data\AuthorisationData;
use App\Database\TransactionInterface;
use App\Factory\LoggerFactory;
use Psr\Log\LoggerInterface;

/**
 * Description of the common service functionality.
 */
trait BaseManipulationServiceTrait
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

        $this->transaction->begin();
        // Executes the repository instructions assigned to the service.
        $result = $this->serviceExecution($data);
        $this->transaction->commit();

        // Logging
        $this->serviceLog($data, $result);

        return $result;
    }

    /**
     * Creates a log entry for successfully performing the service.
     * @param array $inputData Input data.
     * @param object|array|null $resultData Result data.
     * @return void
     */
    protected function serviceLog(array $inputData, object|array|null $resultData): void
    {
        // Service name
        $serviceClass = explode("\\", static::class);
        $serviceName = end($serviceClass);

        // Log message
        $message = "$serviceName successfully";
        if (is_array($resultData)) {
            $resultData = (object)$resultData;
        }
        if (isset($resultData) and isset($resultData->id)) {
            $message = "$message: $resultData->id";
        } elseif (array_key_exists("id", $inputData)) {
            $id = $inputData["id"];
            $message = "$message: $id";
        }

        // Logging
        //$this->logger->info($message);
    }
}
