<?php

namespace App\Domain\Base\Repository;

use App\Domain\Session\Type\SessionRoleType;
use Cake\Database\Query;

/**
 * Trait for checking the access role.
 */
trait AuthorisationRoleTrait
{
    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param Query $query Database query that determines the role.
     * @param bool $checkParentRole If true, then determine the role from the parent element.
     * @param bool $checkReadRole If true, then determine the parent read role.
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    private function getAuthorisationRoleFromQuery(
        ?string $id,
        Query $query,
        bool $checkParentRole = true,
        bool $checkReadRole = false,
        string | null $detailEntity = null
    ): ?string {
        $authorisation = $this->getAuthorisation();
        if (is_null($id)) {
            return SessionRoleType::mapAuthorisationType($authorisation->type);
        } else {
            $statement = $query->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                if ($checkParentRole) {
                    $parentId = $statement->fetch("assoc")[$this->getParentIdName()];
                    if ($checkReadRole)
                        return $this->getParentRepository()->getAuthorisationReadRole($parentId);
                    return $this->getParentRepository()->getAuthorisationRole($parentId, $detailEntity);
                } else {
                    $result = $statement->fetch("assoc");
                    return strtoupper($result["role"]);
                }
            }
        }

        return null;
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param string|null $entityName Name of the rights table.
     * @param bool $checkParentRole If true, then determine the role from the parent element.
     * @param bool $checkReadRole If true, then determine the parent read role.
     * @param string|null $detailEntity Detail entity which should be modified
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    private function getAuthorisationRoleFromCondition(
        ?string $id,
        array $conditions = [],
        ?string $entityName = null,
        bool $checkParentRole = true,
        bool $checkReadRole = false,
        string | null $detailEntity = null
    ): ?string {
        if (is_null($entityName)) {
            $entityName = $this->getEntityName();
        }
        if (sizeof($conditions) === 0) {
            $conditions = ["id" => $id];
        }
        $query = $this->queryFactory->newSelect($entityName);
        $query->select(["*"])
            ->andWhere($conditions);
        return $this->getAuthorisationRoleFromQuery($id, $query, $checkParentRole, $checkReadRole, $detailEntity);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @param string|null $detailEntity Detail entity which should be modified
     * @param bool $checkReadRole If true, then determine the parent read role.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationRole(
        ?string $id,
        string | null $detailEntity = null,
        bool $checkReadRole = false
    ): ?string {
        return $this->getAuthorisationRoleFromCondition($id, ["id" => $id], null, true, $checkReadRole, $detailEntity);
    }

    /**
     * Checks whether the user is authorised to delete the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationDeleteRole(?string $id): ?string
    {
        return $this->getAuthorisationRole($id, null, true);
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     * @throws GenericException
     */
    public function getAuthorisationReadRole(?string $id): ?string
    {
        return $this->getAuthorisationRole($id, null, true);
    }
}
