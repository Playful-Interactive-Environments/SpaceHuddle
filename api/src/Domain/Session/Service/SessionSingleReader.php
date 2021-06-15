<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Data\AuthorisationType;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Service\ServiceSingleReaderTrait;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\LoggerFactory;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionSingleReader
{
    use ServiceSingleReaderTrait {
        ServiceSingleReaderTrait::service as private genericReadService;
    }
    use SessionServiceTrait;

    /**
     * Define authorised roles for the service.
     */
    protected function setPermission(): void
    {
        $this->authorisationPermissionList = [
            AuthorisationType::USER,
            AuthorisationType::PARTICIPANT
        ];
        $this->entityPermissionList = [
            SessionRoleType::MODERATOR,
            SessionRoleType::FACILITATOR,
            SessionRoleType::PARTICIPANT
        ];
    }

    /**
     * Functionality of the read single entity service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|object|null Service output
     * @throws AuthorisationException|GenericException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|object|null {
        $this->genericReadService($authorisation, $bodyData, $urlData);
        $id = $urlData["id"];
        $result = $this->repository->getByIdAuthorised($id, $authorisation);
        return $result;
    }
}
