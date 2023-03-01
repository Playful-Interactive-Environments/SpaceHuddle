<?php


namespace App\Domain\Base\Repository;

use App\Domain\Base\Data\ModificationData;
use Cake\Database\Query;
use Selective\ArrayReader\ArrayReader;

/**
 * Trait that provides the update database entries functionality.
 */
trait ModificationTrait
{
    /**
     * format timestamp
     * @param Query $query Cake PHP Query
     * @return ModificationData Modification Data
     */
    private function getLastModificationTimestamp(Query $query): ModificationData
    {
        $now = date_create()->getTimestamp();
        $rows = $query->execute()->fetchAll();
        if (is_array($rows) and sizeof($rows) > 0) {
            return new ModificationData([
                "lastModification" => strtotime($rows[0][0]),
                "callTimestamp" => $now,
                "rowCount" => sizeof($rows)
            ]);
        }
        return ModificationData::getEmpty();
    }

    /**
     * Has entity changes
     * @param array $conditions The WHERE conditions to add with AND.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByConditions(array $conditions = []): ModificationData
    {
        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["modification_date"])
            ->andWhere($conditions)
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Has entity changes
     * @param array $conditions The WHERE conditions to add with AND.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByConditionsAuthorised(array $conditions = []): ModificationData
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = $this->getAuthorisationCondition($authorisation);

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["modification_date"])
            ->andWhere($conditions)
            ->andWhere($authorisation_conditions)
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Has entity changes
     * @param string $id The entity ID.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationById(string $id): ModificationData
    {
        return $this->lastModificationByConditions(["id" => $id]);
    }

    /**
     * Has changes for the parent ID
     * @param string $parentId The entity parent ID.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByParentId(string $parentId): ModificationData
    {
        return $this->lastModificationByConditions([$this->getParentIdName() => $parentId]);
    }
}
