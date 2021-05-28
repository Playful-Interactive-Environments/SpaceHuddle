<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Topic;

/**
 * Controller for topics.
 * @package PieLab\GAB\Controllers
 */
class TopicController extends AbstractController
{
    /**
     * Creates a new TopicController.
     */
    protected function __construct()
    {
        parent::__construct("topic", Topic::class, SessionController::class, "session", "session_id");
    }

    /**
     * List all topics for the session.
     * @param string|null $sessionId The session's ID.
     * @return string A list of topics for the session.
     * @OA\Get(
     *   path="/api/session/{sessionId}/topics/",
     *   summary="List of all topics for the session.",
     *   tags={"Topic"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Topic")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAll(?string $sessionId = null): string
    {
        return parent::readAllGeneric($sessionId);
    }

    /**
     * Retrieves detailed data for the topic with a specified ID.
     * @param string|null $id The topic's ID.
     * @return string Detailed topic data.
     * @OA\Get(
     *   path="/api/topic/{id}/",
     *   summary="Detail data for the topic with the specified id.",
     *   tags={"Topic"},
     *   @OA\Parameter(in="path", name="id", description="ID of topic to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Topic"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $id = null): string
    {
        return parent::readGeneric($id);
    }

    /**
     * Create a new topic for the session.
     * @param string|null $sessionId The session's ID.
     * @param string|null $title The topic title.
     * @param string|null $description The topic description.
     * @return string The topic data in JSON format.
     * @OA\Post(
     *   path="/api/session/{sessionId}/topic/",
     *   summary="Create a new topic for the session.",
     *   tags={"Topic"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"title", "description"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Topic"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function add(?string $sessionId = null, ?string $title = null, ?string $description = null): string
    {
        $params = $this->formatParameters(
            [
                "session_id" => ["default" => $sessionId, "url" => "session", "required" => true],
                "title" => ["default" => $title, "required" => true],
                "description" => ["default" => $description, "required" => true]
            ]
        );

        return $this->addGeneric($params->session_id, $params);
    }

    /**
     * Update a topic.
     * @param string|null $id The topic's ID.
     * @param string|null $title The topic's title.
     * @param string|null $description The topic's description.
     * @param string|null $activeTaskId The active task ID.
     * @return string The updated topic data in JSON format.
     * @OA\Put(
     *   path="/api/topic/",
     *   summary="Update a topic.",
     *   tags={"Topic"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="json",
     *         @OA\Schema(ref="#/components/schemas/Topic")
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Topic"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(
        ?string $id = null,
        ?string $title = null,
        ?string $description = null,
        ?string $activeTaskId = null
    ): string {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id],
                "title" => ["default" => $title],
                "description" => ["default" => $description],
                "active_task_id" => ["default" => $activeTaskId, "requestKey" => "activeTaskId"]
            ]
        );

        return $this->updateGeneric($params->id, $params);
    }

    /**
     * Delete a topic.
     * @param string|null $id The topic's ID.
     * @return string A success or failure message in JSON format.
     * @OA\Delete(
     *   path="/api/topic/{id}/",
     *   summary="Delete a topic.",
     *   tags={"Topic"},
     *   @OA\Parameter(in="path", name="id", description="ID of topic to delete", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $id = null): string
    {
        return parent::deleteGeneric($id);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
        $query = "SELECT * FROM task WHERE topic_id = :topic_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":topic_id", $id);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        $task = TaskController::getInstance();
        foreach ($resultData as $resultItem) {
            $task_id = $resultItem["id"];
            $task->delete($task_id);
        }

        $query = "SELECT * FROM selection_group WHERE topic_id = :topic_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":topic_id", $id);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        $selection = SelectionController::getInstance();
        foreach ($resultData as $resultItem) {
            $selectionId = $resultItem["id"];
            $selection->delete($selectionId);
        }
    }
}
