<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\StateTask;
use PieLab\GAB\Models\Task;

/**
 * Controller class for clients.
 * @package PieLab\GAB\Controllers
 */
class ClientController extends AbstractController
{
    /**
     * Creates a new ClientController.
     */
    protected function __construct()
    {
        parent::__construct("task", Task::class, TopicController::class, "topic", "topic_id");
    }

    /**
     * Set the client application state for the task.
     * @param string|null $taskId The task's ID.
     * @param StateTask|null $state The client state.
     * @return string Updated data in JSON format.
     * @OA\Put(
     *   path="/api/task/{taskId}/client_application_state/{state}/",
     *   summary="Set the client application state for the task.",
     *   tags={"Client Application"},
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task to be updated", required=true),
     *   @OA\Parameter(in="path", name="state",
     *     description="display status on the client devices",
     *     required=true,
     *     @OA\Schema(ref="#/components/schemas/StateTask")),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function setClient(?string $taskId = null, StateTask|null $state = null): string
    {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $taskId, "url" => "task", "required" => true],
                "state" => ["default" => $state, "type" => StateTask::class, "url" => "client_application_state", "required" => true]
            ]
        );

        return $this->updateGeneric($params->id, $params);
    }
}
