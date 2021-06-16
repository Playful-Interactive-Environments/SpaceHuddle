<?php

namespace App\Domain\Base\Repository;

use App\Factory\QueryFactory;

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
    use DeleteTrait;
    use CheckTrait;

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
}
