<?php

namespace App\Domain\Session\Service;

use App\Data\AuthorisationException;
use App\Database\TransactionInterface;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
use App\Data\AuthorisationRoleType;
use App\Domain\Base\Service\AbstractService;
use App\Domain\Session\Repository\SessionRepository;
use App\Domain\User\Type\UserRoleType;
use App\Factory\LoggerFactory;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionReader extends AbstractService
{
    /**
     * The constructor.
     *
     * @param SessionRepository $repository The repository
     * @param SessionValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SessionRepository $repository,
        SessionValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $transaction, $loggerFactory);
        $this->authorisationPermissionList = [AuthorisationRoleType::USER];
        $this->entityPermissionList = [
            UserRoleType::MODERATOR,
            UserRoleType::FACILITATOR
        ];
    }

    /**
     * Functionality of the read all service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $data The form data
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(AuthorisationData $authorisation, array $data): array|AbstractData|null
    {
        parent::service($authorisation, $data);
        return $this->repository->getAll($authorisation->id);
    }
}
