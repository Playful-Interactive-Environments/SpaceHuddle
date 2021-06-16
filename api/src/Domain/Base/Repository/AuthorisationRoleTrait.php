<?php

namespace App\Domain\Base\Repository;

use App\Data\AuthorisationData;
use App\Domain\Session\Type\SessionRoleType;

/**
 * Trait for checking the access role.
 */
trait AuthorisationRoleTrait
{
    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        if (is_null($id)) {
            return SessionRoleType::mapAuthorisationType($authorisation->type);
        } else {
            $query = $this->queryFactory->newSelect($this->getEntityName());
            $query->select(["*"])
                ->andWhere(["id" => $id]);
            $statement = $query->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $parentId = $statement->fetch("assoc")[$this->getParentIdName()];
                return $this->getParentRepository()->getAuthorisationRole($authorisation, $parentId);
            }
        }

        return null;
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param AuthorisationData $authorisation Authorisation token data.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(AuthorisationData $authorisation, ?string $id): ?string
    {
        return $this->getAuthorisationRole($authorisation, $id);
    }
}
