<?php

namespace App\Domain\Base\Repository;

use App\Factory\QueryFactory;
use Psr\Log\LoggerInterface;

/**
 * Description of the common repository functionality.
 */
trait RepositoryTrait
{
    use GenericTrait;
    use AuthorisationRoleTrait;
    use SelectTrait;
    use InsertTrait, UpdateTrait {
        InsertTrait::formatDatabaseInput insteadof UpdateTrait;
    }
    use CloneTrait;
    use DeleteTrait;
    use CheckTrait;
    use AuthorisationTrait;

    protected QueryFactory $queryFactory;

    /**
     * Basic setup for constructor.
     * @param QueryFactory $queryFactory The query factory
     * @param string|null $entityName Name of the database table
     * @param string|null $resultClass Name of the result class
     * @param string|null $parentIdName Column name of the parent ID.
     * @param string|null $parentRepository The parent repository class.
     * @return void
     */
    protected function setUp(
        QueryFactory $queryFactory,
        ?string $entityName = null,
        ?string $resultClass = null,
        ?string $parentIdName = null,
        ?string $parentRepository = null
    ): void {
        $this->queryFactory = $queryFactory;
        $this->setGenerics($queryFactory, $entityName, $resultClass, $parentIdName, $parentRepository);
    }

    protected function convertBoolToTinyInt(bool | null $value): int | null
    {
        if (!isset($value)) {
            return null;
        }
        if ($value) {
            return 1;
        }
        return 0;
    }


    protected LoggerInterface $logger;
    public function setLogger(LoggerInterface $logger): void  {
        $this->logger = $logger;
    }
}
