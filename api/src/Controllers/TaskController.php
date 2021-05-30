<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\ParticipantTask;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\Session;
use PieLab\GAB\Models\StateModule;
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
     *       mediaType="application/json",
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

        $parameterDependencies = $this->formatParameters(
            [
                "module_name" => ["default" => $params->task_type],
                "order" => ["default" => 1],
                "state" => ["default" => StateModule::ACTIVE, "type" => StateModule::class]
            ]
        );

        return $this->addGeneric($params->topic_id, $params, parameterDependencies: $parameterDependencies);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     */
    protected function addDependencies(string $id, array|object|null $parameter)
    {
        $moduleId = self::uuid();
        $query = "INSERT INTO module (`id`, `task_id`, `module_name`, `order`, `state`) 
            VALUES (:id, :task_id, :module_name, :order, :state)";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":id", $moduleId);
        $statement->bindParam(":task_id", $id);
        $statement->bindParam(":module_name", $parameter->module_name);
        $statement->bindParam(":order", $parameter->order);
        $statement->bindParam(":state", $parameter->state);
        $statement->execute();
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
     *         mediaType="application/json",
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

        $query = "UPDATE topic
          SET active_task_id = null
          WHERE active_task_id = :task_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_id", $id);
        $statement->execute();

        $query = "SELECT * FROM module WHERE task_id = :task_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_id", $id);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        $idea = ModuleController::getInstance();
        foreach ($resultData as $resultItem) {
            $ideaId = $resultItem["id"];
            $idea->delete($ideaId);
        }
    }

    /**
     * Get the aktive task to be displayed on the public screen for the session.
     * @param string|null $sessionId The session ID.
     * @param string|null $taskId The task ID.
     * @return string Returns the success message in JSON format.
     * @OA\Get(
     *   path="/api/session/{sessionId}/public_screen/",
     *   summary="Get the aktive task to be displayed on the public screen for the session.",
     *   tags={"Public Screen"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session to be displayed", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Task"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function getPublicScreen(?string $sessionId = null): string
    {
        if (is_null($sessionId)) {
            $sessionId = $this->getUrlParameter("session");
        }
        $query = "SELECT * FROM task
          WHERE id IN (
            SELECT module.task_id
            FROM session 
            INNER JOIN module ON module.id = session.public_screen_module_id
            WHERE session.id = :sessionId)";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":sessionId", $sessionId);
        $statement->execute();
        $result = $this->database->fetchFirst($statement);
        http_response_code(200);
        return json_encode(new Task($result));
    }
}
