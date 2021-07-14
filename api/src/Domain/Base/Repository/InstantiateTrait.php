<?php

namespace App\Domain\Base\Repository;

trait InstantiateTrait
{
    /**
     * Copy repository parameter to a specific repository type.
     * @param string $repositoryName Repository type.
     * @return RepositoryInterface Copied instance.
     */
    public function copy(
        string $repositoryName
    ): RepositoryInterface {
        $repository = new $repositoryName($this->queryFactory);
        $repository->setAuthorisation($this->getAuthorisation());
        return $repository;
    }
}
