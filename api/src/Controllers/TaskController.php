<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\StateTask;
use PieLab\GAB\Models\Task;
use PieLab\GAB\Models\TaskType;

/**
 * Controller for tasks.
 * @package PieLab\GAB\Controllers
 */
class TaskController extends AbstractController
{
    /**
     * Construct a new TaskController.
     */
    protected function __construct()
    {
        parent::__construct("task", Task::class, TopicController::class, "topic", "topic_id");
    }

    /**
     * List all tasks for a topic.
     * @param string|null $topicId The topic's ID.
     * @return string A list of all tasks in JSON format.
     * @OA\Get(
     *   path="/api/topic/{topicId}/tasks/",
     *   summary="List of all tasks for the topic.",
     *   tags={"Task"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Task")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAll(?string $topicId = null): string
    {
        return parent::readAllGeneric($topicId);
    }

    /**
     * Detail data for the task with the specified ID.
     * @param string|null $id The task's ID.
     * @return string Detailed task data.
     * @OA\Get(
     *   path="/api/task/{id}/",
     *   summary="Detail data for the task with the specified id.",
     *   tags={"Task"},
     *   @OA\Parameter(in="path", name="id", description="ID of task to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Task"),
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
     * Create a new task for the topic.
     * @param string|null $topicId The topic's ID.
     * @param string|null $taskType The task's type.
     * @param string|null $name The task's name.
     * @param string|null $parameter The task's parameters.
     * @param string|null $order The task order.
     * @return string The new task in JSON format.
     * @OA\Post(
     *   path="/api/topic/{topicId}/task/",
     *   summary="Create a new task for the topic.",
     *   tags={"Task"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"taskType"},
     *         @OA\Property(property="taskType", type="string"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="parameter", type="object", format="json"),
     *         @OA\Property(property="order", type="integer")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Task"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function add(
        ?string $topicId = null,
        ?string $taskType = null,
        ?string $name = null,
        ?string $parameter = null,
        ?string $order = null
    ): string {
        $params = $this->formatParameters(
            [
                "topic_id" => ["default" => $topicId, "url" => "topic", "required" => true],
                "task_type" => ["default" => $taskType, "type" => TaskType::class, "requestKey" => "taskType", "required" => true],
                "name" => ["default" => $name],
                "parameter" => ["default" => $parameter, "type" => "JSON"],
                "order" => ["default" => $order],
                "state" => ["default" => StateTask::WAIT, "type" => StateTask::class]
            ]
        );

        return $this->addGeneric($params->topic_id, $params);
    }

    /**
     * Update a task.
     * @param string|null $id The task's ID.
     * @param string|null $taskType The task's type.
     * @param string|null $name The task's name.
     * @param string|null $parameter The task's parameters.
     * @param string|null $order The task order.
     * @param string|null $state The task's state.
     * @return string
     * @OA\Put(
     *   path="/api/task/",
     *   summary="Update a task.",
     *   tags={"Task"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\MediaType(
     *         mediaType="json",
     *         @OA\Schema(ref="#/components/schemas/Task")
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Task"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(
        ?string $id = null,
        ?string $taskType = null,
        ?string $name = null,
        ?string $parameter = null,
        ?string $order = null,
        ?string $state = null
    ): string {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id],
                "task_type" => ["default" => $taskType, "type" => TaskType::class, "requestKey" => "taskType"],
                "name" => ["default" => $name],
                "parameter" => ["default" => $parameter, "type" => "JSON"],
                "order" => ["default" => $order],
                "state" => ["default" => $state, "type" => StateTask::class]
            ]
        );

        return $this->updateGeneric($params->id, $params);
    }

    /**
     * Delete a task.
     * @param string|null $id The task's ID.
     * @return string A success or failure message in JSON format.
     * @OA\Delete(
     *   path="/api/task/{id}/",
     *   summary="Delete a task.",
     *   tags={"Task"},
     *   @OA\Parameter(in="path", name="id", description="ID of task to delete", required=true),
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
        $query = "SELECT * FROM idea WHERE task_id = :task_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_id", $id);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        $idea = IdeaController::getInstance();
        foreach ($resultData as $resultItem) {
            $ideaId = $resultItem["id"];
            $idea->delete($ideaId);
        }

        $query = "DELETE FROM voting WHERE task_id = :task_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_id", $id);
        $statement->execute();
    }
}
