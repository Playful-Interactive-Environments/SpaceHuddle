<?php

namespace App\Domain\Session\Service;

use App\Domain\Base\Data\AuthorisationRole;
use App\Domain\Base\Service\ServiceSingleReader;
use App\Domain\Session\Repository\SessionRepository;
use App\Factory\LoggerFactory;

/**
 * Read all session service
 * @package App\Domain\Session\Service
 */
class SessionSingleReader extends ServiceSingleReader
{
    /**
     * The constructor.
     *
     * @param SessionRepository $repository The repository
     * @param SessionValidator $validator The validator
     * @param LoggerFactory $loggerFactory The logger factory
     */
    public function __construct(
        SessionRepository $repository,
        SessionValidator $validator,
        LoggerFactory $loggerFactory
    ) {
        parent::__construct($repository, $validator, $loggerFactory);
        $this->permission = [AuthorisationRole::USER];
    }
}
