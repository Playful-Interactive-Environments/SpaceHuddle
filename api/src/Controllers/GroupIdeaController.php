<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Idea;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\TaskType;

/**
 * Controller for category ideas.
 * @package PieLab\GAB\Controllers
 */
class GroupIdeaController extends AbstractController
{
    /**
     * Creates a new GroupIdeaController.
     */
    protected function __construct()
    {
        parent::__construct("hierarchy", Idea::class, GroupController::class, "group", "group_id", "category");
        $this->taskType = TaskType::CATEGORISATION;
    }

    /**
     * Read the ideas for the category with the specified ID.
     * @param string|null $categoryId The category's ID.
     * @return string Returns a json encoded list of all ideas for this category.
     * @OA\Get(
     *   path="/api/category/{categoryId}/ideas",
     *   summary="Ideas for the category with the specified id.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="categoryId", description="ID of category to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Idea")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readIdeas(?string $groupId = null): string
    {
        $taskType = strtoupper(TaskType::BRAINSTORMING);
        $query = "SELECT * FROM idea WHERE task_id IN (SELECT id FROM task WHERE task_type like :task_type)
                     AND id IN (SELECT sub_idea_id FROM hierarchy WHERE group_idea_id = :group_id)";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_type", $taskType);
        return parent::readAllGeneric(
            $groupId,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            statement: $statement
        );
    }

    /**
     * Add a list of ideas to a category.
     * @param string|null $categoryId The category's ID.
     * @param array|null $ideaArray The list of ideas to add.
     * @return string Inserted data in the json format.
     * @OA\Post(
     *   path="/api/category/{categoryId}/ideas/",
     *   summary="Add list of idea_ids to a category.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="categoryId", description="ID of the category", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="array",
     *         @OA\Items( type="string", example="uuid")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function addIdeas(?string $groupId = null, ?array $ideaArray = null): string
    {
        $params = $this->formatParameters(
            [
                "group_idea_id" => ["default" => $groupId, "url" => "category", "required" => true],
                "sub_idea_id" => ["default" => $ideaArray, "type" => "ARRAY", "result" => "all"]
            ]
        );
        $list = [];
        foreach ($params->sub_idea_id as $value) {
            array_push(
                $list,
                [
                    "group_idea_id" => $params->group_idea_id,
                    "sub_idea_id" => $value
                ]
            );
        }
        return $this->addGeneric(
            $params->group_idea_id,
            $list,
            insertId: false,
            duplicateCheck: "WHERE NOT EXISTS( SELECT 1 FROM hierarchy WHERE group_idea_id = :group_idea_id
            AND sub_idea_id = :sub_idea_id ) LIMIT 1"
        );
    }

    /**
     * Delete a list of ideas from a category.
     * @param string|null $categoryId The category's ID.
     * @param array|null $ideaArray The list of ideas to delete.
     * @return string Success status of the statement.
     * @OA\Delete(
     *   path="/api/category/{categoryId}/ideas/",
     *   summary="Delete the list of idea_ids from a category.",
     *   tags={"Category"},
     *   @OA\Parameter(in="path", name="categoryId", description="ID of the category", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(type="array",
     *         @OA\Items( type="string", example="uuid")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function deleteIdeas(?string $groupId = null, array $ideaArray = null): string
    {
        $params = $this->formatParameters(
            [
                "group_idea_id" => ["default" => $groupId, "url" => "category", "required" => true],
                "sub_idea_id" => ["default" => $ideaArray, "type" => "ARRAY", "result" => "all"]
            ]
        );

        $sub_idea_ids = implode(',', $params->sub_idea_id);
        $query = "DELETE FROM hierarchy WHERE group_idea_id = :group_id AND FIND_IN_SET(sub_idea_id, :idea_id) ";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":group_id", $params->group_idea_id);
        $stmt->bindParam(":idea_id", $sub_idea_ids);

        return parent::deleteGeneric(
            $params->group_idea_id,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR],
            statement: $stmt
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        return GroupController::getInstanceAuthorisationRole($id);
    }
}
