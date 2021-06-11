<?php

namespace App\Domain\Base\Service;

use App\Data\AuthorisationType;
use App\Database\TransactionInterface;
use App\Domain\Base\Repository\AbstractRepository;
use App\Domain\Base\Data\AbstractData;
use App\Data\AuthorisationData;
use App\Data\AuthorisationException;
use App\Domain\Session\Type\SessionRoleType;
use App\Factory\LoggerFactory;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Log\LoggerInterface;

/**
 * Description of the common service functionality.
 * @package App\Domain\Base\Service
 */
abstract class AbstractService
{
    protected AbstractRepository $repository;
    protected AbstractValidator $validator;
    protected TransactionInterface $transaction;
    protected LoggerInterface $logger;
    protected array $authorisationPermissionList;
    protected array $entityPermissionList;

    /**
     * The constructor.
     *
     * @param AbstractRepository $repository The repository
     * @param AbstractValidator $validator The validator
     * @param TransactionInterface $transaction The transaction
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        AbstractRepository $repository,
        AbstractValidator $validator,
        TransactionInterface $transaction,
        LoggerFactory $loggerFactory
    ) {
        $this->repository = $repository;
        $this->validator = $validator;
        $this->transaction = $transaction;
        $this->logger = $loggerFactory
            ->addFileHandler("service.log")
            ->createLogger();
        $this->authorisationPermissionList = [
            AuthorisationType::PARTICIPANT,
            AuthorisationType::USER,
            AuthorisationType::NONE
        ];
        $this->entityPermissionList = [
            SessionRoleType::MODERATOR,
            SessionRoleType::FACILITATOR,
            SessionRoleType::PARTICIPANT
        ];
    }

    /**
     * Check if the user role is part of the authorised roles.
     * @param string|null $role User role
     * @param array $authorizedRoles List of authorised roles.
     * @return bool Authorisation state
     */
    protected static function isAuthorized(?string $role, array $authorizedRoles = []): bool
    {
        if (isset($role)) {
            $role = strtoupper($role);
            $authorizedRoles = array_map("strtoupper", $authorizedRoles);
            return (
                sizeof($authorizedRoles) === 0 or
                in_array($role, $authorizedRoles)
            );
        }
        return false;
    }

    /**
     * Is authorisation required for the service and if so, which one?
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $urlData Url parameter from the request
     * @return bool TRUE if the rights for executing the service are available.
     */
    public function hasPermission(AuthorisationData $authorisation, array $urlData): bool
    {
        $permission = self::isAuthorized($authorisation->type, $this->authorisationPermissionList);

        if ($permission) {
            if (key_exists("id", $urlData)) {
                $id = $urlData["id"];
                $specificEntityRole = $this->repository->getAuthorisationRole($authorisation, $id);
                $permission = self::isAuthorized($specificEntityRole, $this->entityPermissionList);
            } elseif (sizeof($urlData) > 0) {
                $urlParameterName = array_key_first($urlData);
                if (str_ends_with($urlParameterName, "Id")) {
                    $id = $urlData[$urlParameterName];
                    $repository = $this->repository->getParentRepository();
                    if (isset($repository)) {
                        $specificEntityRole = $repository->getAuthorisationRole($authorisation, $id);
                        $permission = self::isAuthorized($specificEntityRole, $this->entityPermissionList);
                    }
                }
            }
        }

        return $permission;
    }

    /**
     * Functionality of the service.
     *
     * @param AuthorisationData $authorisation Authorisation data
     * @param array<string, mixed> $bodyData Form data from the request body
     * @param array<string, mixed> $urlData Url parameter from the request
     *
     * @return array|AbstractData|null Service output
     * @throws AuthorisationException
     */
    public function service(
        AuthorisationData $authorisation,
        array $bodyData,
        array $urlData
    ): array|AbstractData|null {
        if (!$this->hasPermission($authorisation, $urlData)) {
            http_response_code(StatusCodeInterface::STATUS_UNAUTHORIZED);
            throw new AuthorisationException("User has no rights for this service or entity");
        }

        return null;
    }
}
