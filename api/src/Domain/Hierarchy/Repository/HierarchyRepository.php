<?php

namespace App\Domain\Hierarchy\Repository;

use App\Data\AuthorisationData;
use App\Domain\Base\Data\ModificationData;
use App\Domain\Base\Repository\GenericException;
use App\Domain\Base\Repository\RepositoryInterface;
use App\Domain\Base\Repository\RepositoryTrait;
use App\Domain\Hierarchy\Data\HierarchyData;
use App\Domain\Idea\Repository\IdeaTableTrait;
use App\Domain\Task\Repository\TaskRepository;
use App\Domain\Task\Type\TaskState;
use App\Factory\QueryFactory;

/**
 * Repository
 */
class HierarchyRepository implements RepositoryInterface
{
    use RepositoryTrait, IdeaTableTrait {
        IdeaTableTrait::getById insteadof RepositoryTrait;
        IdeaTableTrait::deleteDependencies as private parentDeleteDependencies;
        IdeaTableTrait::cloneDependencies insteadof RepositoryTrait;
        IdeaTableTrait::cloneColumns insteadof RepositoryTrait;
        IdeaTableTrait::formatDatabaseInput insteadof RepositoryTrait;
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     * @return void
     */
    protected function deleteDependencies(string $id): void
    {
        $children = [];
        $result = $this->get([
            "hierarchy.category_idea_id" => $id
        ]);
        if (is_array($result)) {
            $children = $result;
        } elseif (isset($result)) {
            $children = [$result];
        }

        $this->parentDeleteDependencies($id);

        foreach ($children as $child) {
            $this->deleteById($child->id);
        }
    }

    /**
     * The constructor.
     *
     * @param QueryFactory $queryFactory The query factory
     */
    public function __construct(QueryFactory $queryFactory)
    {
        $this->setUp(
            $queryFactory,
            "idea",
            HierarchyData::class,
            "task_id",
            TaskRepository::class
        );
    }

    /**
     * Gets the authorisation condition for the entity.
     * @param AuthorisationData $authorisation Current authorisation data.
     * @return array authorisation condition
     */
    protected function getAuthorisationCondition(AuthorisationData $authorisation): array
    {
        $authorisation_conditions = [];
        if ($authorisation->isParticipant()) {
            $authorisation_conditions = [
                "task.state IN" => [
                    strtoupper(TaskState::ACTIVE),
                    strtoupper(TaskState::READ_ONLY),
                    strtoupper(TaskState::WAIT),
                    strtoupper(TaskState::DONE)
                ]
            ];
        }
        return $authorisation_conditions;
    }

    /**
     * Get entity.
     * @param array $conditions The WHERE conditions to add with AND.
     * @param array $sortConditions The ORDER BY conditions.
     * @return HierarchyData|array<HierarchyData>|null The result entity(s).
     * @throws GenericException
     */
    public function get(array $conditions = [], array $sortConditions = []): null|HierarchyData|array
    {
        if (count($sortConditions) == 0) {
            $sortConditions = ["order"];
        }

        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = $this->getAuthorisationCondition($authorisation);

        $subQueryIdeas = $this->queryFactory->newSelect("idea AS parent_idea")
            ->select(["id"])
            ->where([
                function ($exp, $q) {
                    return $exp->equalFields("parent_idea.id", "hierarchy.category_idea_id");
                },
                function ($exp, $q) {
                    return $exp->equalFields("parent_idea.task_id", "idea.task_id");
                },
            ]);

        $subQueryChildCount = $this->queryFactory->newSelect("hierarchy AS children")
            ->select(function ($q) {
                return ['count' => $q->func()->count('children.sub_idea_id')];
            })
            ->where([
                function ($exp, $q) {
                    return $exp->equalFields("children.category_idea_id", "idea.id");
                },
            ]);

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select([
            "idea.id",
            "idea.keywords",
            "idea.description",
            "idea.image_timestamp",
            "idea.link",
            "idea.order",
            "idea.parameter",
            "idea.participant_id",
            "participant.symbol",
            "participant.color",
            "idea.state",
            "idea.task_id",
            "idea.timestamp",
            "hierarchy.category_idea_id AS parent_id",
            "child_count" => $subQueryChildCount
        ])
            ->leftJoin("participant", "participant.id = idea.participant_id")
            ->innerJoin("task", "task.id = idea.task_id")
            ->leftJoin("hierarchy", [
                "hierarchy.sub_idea_id = idea.id",
                function ($exp, $q) use ($subQueryIdeas) {
                    return $exp->exists($subQueryIdeas);
                }
            ])
            ->andWhere($authorisation_conditions)
            ->andWhere($conditions)
            ->order($sortConditions);

        $result = $this->fetchAll($query);
        if (is_array($result)) {
            foreach ($result as $resultItem) {
                $this->getDetails($resultItem, $authorisation);
            }
        } elseif (is_object($result)) {
            $this->getDetails($result, $authorisation);
        }
        return $result;
    }

    /**
     * Has entity changes
     * @param array $conditions The WHERE conditions to add with AND.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByConditions(array $conditions = []): ModificationData
    {
        $authorisation = $this->getAuthorisation();
        $authorisation_conditions = $this->getAuthorisationCondition($authorisation);

        $query = $this->queryFactory->newSelect($this->getEntityName());
        $query->select(["modification_date"])
            ->innerJoin("task", "task.id = idea.task_id")
            ->andWhere($authorisation_conditions)
            ->andWhere($conditions)
            ->order(["modification_date" => "DESC"]);

        return $this->getLastModificationTimestamp($query);
    }

    /**
     * Set Properties
     * @param AuthorisationData $authorisation Authorisation data
     * @param HierarchyData $data Idea data
     */
    private function getDetails(HierarchyData $data, AuthorisationData $authorisation): void
    {
        if ($authorisation->isParticipant()) {
            $data->isOwn = ($data->participantId == $authorisation->id);
        }
    }

    /**
     * Get list of entities for the parent ID.
     * @param string $taskId The entity parent ID.
     * @param string|null $hierarchyParentId The hierarchy parent ID.
     * @return array<HierarchyData> The result entity list.
     * @throws GenericException
     */
    public function getAllForParent(string $taskId, string | null $hierarchyParentId): array
    {
        $conditions = [
            "idea.task_id" => $taskId
        ];
        if (isset($hierarchyParentId)) {
            $conditions["hierarchy.category_idea_id"] = $hierarchyParentId;
        } else {
            array_push($conditions, "hierarchy.category_idea_id IS NULL");
        }
        $resultList = [];
        $result = $this->get($conditions, ["idea.order"]);
        if (is_array($result)) {
            $resultList = $result;
        } elseif (isset($result)) {
            $resultList = [$result];
        }
        return $resultList;
    }

    /**
     * Has changes for the parent ID
     * @param string $parentId The entity parent ID.
     * @return ModificationData Modification Data
     * @throws GenericException
     */
    public function lastModificationByParentSpecial(string $taskId, string | null $hierarchyParentId): ModificationData
    {
        $conditions = [
            "idea.task_id" => $taskId
        ];
        if (isset($hierarchyParentId)) {
            $conditions["hierarchy.category_idea_id"] = $hierarchyParentId;
        } else {
            array_push($conditions, "hierarchy.category_idea_id IS NULL");
        }
        return $this->lastModificationByConditions($conditions);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function insertDependencies(string $id, array|object|null $parameter): void
    {
        if (is_object($parameter) and isset($parameter->parentId)) {
            $this->queryFactory->newInsert("hierarchy", [
                "category_idea_id" => $parameter->parentId,
                "sub_idea_id" => $id,
                "order" => $parameter->order,
            ])->execute();
        }
    }

    /**
     * Update dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     * @return void
     */
    protected function updateDependencies(string $id, array|object|null $parameter): void
    {
        $subQueryIdeas = $this->queryFactory->newSelect("idea AS parent_idea")
            ->select(["child_idea.id"])
            ->join([
                "child_idea" => [
                    "table" => "idea",
                    "type" => "INNER",
                    "conditions" => [
                        function ($exp, $q) {
                            return $exp->equalFields("parent_idea.task_id", "child_idea.task_id");
                        }
                    ]
                ]
            ])
            ->where([
                function ($exp, $q) {
                    return $exp->equalFields("child_idea.id", "hierarchy.sub_idea_id");
                },
                function ($exp, $q) {
                    return $exp->equalFields("parent_idea.id", "hierarchy.category_idea_id");
                }
            ]);

        if (is_object($parameter)) {
            if (isset($parameter->parentId)) {
                $this->queryFactory->newUpdate("hierarchy", [
                    "category_idea_id" => $parameter->parentId,
                    "order" => $parameter->order,
                ])->andWhere([
                    "sub_idea_id" => $id,
                    function ($exp, $q) use ($subQueryIdeas) {
                        return $exp->exists($subQueryIdeas);
                    }
                ])->execute();
            } else {
                $this->queryFactory->newDelete("hierarchy")->andWhere([
                    "sub_idea_id" => $id,
                    function ($exp, $q) use ($subQueryIdeas) {
                        return $exp->exists($subQueryIdeas);
                    }
                ])->execute();
            }
        }
    }
}
