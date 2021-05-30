<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Idea;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\TaskType;

/**
 * Controller for ideas of a selection.
 * @package PieLab\GAB\Controllers
 */
class SelectionIdeaController extends AbstractController
{
    /**
     * Creates a SelectionIdeaController.
     */
    protected function __construct()
    {
        parent::__construct(
            "selection_group_idea",
            Idea::class,
            SelectionController::class,
            "selection",
            "selection_group_id",
            "selection"
        );
    }

    /**
     * Get the ideas for the selection with the specified id.
     * @param string|null $selectionGroupId
     * @return string Returns a list of ideas in JSON format.
     * @OA\Get(
     *   path="/api/selection/{selectionId}/ideas",
     *   summary="Ideas for the selection with the specified id.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="selectionId", description="ID of selection to return", required=true),
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
    public function readIdeas(?string $selectionGroupId = null): string
    {
        $taskType = strtoupper(TaskType::BRAINSTORMING);
        $query = "SELECT * FROM idea
      WHERE task_id IN (SELECT id FROM task WHERE task_type like :task_type)
      AND id IN (SELECT idea_id FROM selection_group_idea WHERE selection_group_id = :selection_group_id)";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_type", $taskType);
        return parent::readAllGeneric(
            $selectionGroupId,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            statement: $statement
        );
    }

    /**
     * Add a list of ideas to the selection.
     * @param string|null $selectionId The selection's ID.
     * @param array|null $ideaArray The idea array.
     * @return string Returns a success or failure statement in JSON format.
     * @OA\Post(
     *   path="/api/selection/{selectionId}/ideas/",
     *   summary="Add list of idea_ids to a selection.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="selectionId", description="ID of the selection", required=true),
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
    public function addIdeas(?string $selectionId = null, ?array $ideaArray = null): string
    {
        $params = $this->formatParameters(
            [
                "selection_group_id" => ["default" => $selectionId, "url" => "selection", "required" => true],
                "idea_id" => ["default" => $ideaArray, "type" => "ARRAY", "result" => "all"]
            ]
        );
        $list = [];
        foreach ($params->idea_id as $value) {
            array_push(
                $list,
                [
                    "selection_group_id" => $params->selection_group_id,
                    "idea_id" => $value
                ]
            );
        }
        return $this->addGeneric(
            $params->selection_group_id,
            $list,
            insertId: false,
            duplicateCheck: "WHERE NOT EXISTS( SELECT 1 FROM selection_group_idea
            WHERE selection_group_id = :selection_group_id AND idea_id = :idea_id ) LIMIT 1"
        );
    }

    /**
     * Delete a list of ideas from a selection.
     * @param string|null $selectionId The selection's ID.
     * @param array|null $ideaArray The idea array.
     * @return string Returns a success or failure statement in JSON format.
     * @OA\Delete(
     *   path="/api/selection/{selectionId}/ideas/",
     *   summary="Delete the list of idea_ids from a selection.",
     *   tags={"Selection"},
     *   @OA\Parameter(in="path", name="selectionId", description="ID of the selection", required=true),
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
    public function deleteIdeas(?string $selectionId = null, array $ideaArray = null): string
    {
        $params = $this->formatParameters(
            [
                "selection_group_id" => ["default" => $selectionId, "url" => "selection", "required" => true],
                "idea_id" => ["default" => $ideaArray, "type" => "ARRAY", "result" => "all"]
            ]
        );

        $ideaIds = implode(",", $params->idea_id);
        $query = "DELETE FROM selection_group_idea WHERE selection_group_id = :selection_group_id
                                   AND FIND_IN_SET(idea_id, :idea_id) ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":selection_group_id", $params->selection_group_id);
        $statement->bindParam(":idea_id", $ideaIds);

        return parent::deleteGeneric(
            $params->selection_group_id,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR],
            statement: $statement
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        return SelectionController::getInstanceAuthorisationRole($id);
    }
}
