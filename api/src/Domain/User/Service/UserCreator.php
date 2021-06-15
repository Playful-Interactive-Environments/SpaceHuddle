<?php

namespace App\Domain\User\Service;

use App\Database\TransactionInterface;
use App\Domain\Base\Service\ServiceCreatorTrait;
use App\Domain\User\Repository\UserRepository;
use App\Factory\LoggerFactory;

/**
 * Service.
 */
final class UserCreator
{
    use ServiceCreatorTrait;
    use UserServiceTrait;
}
