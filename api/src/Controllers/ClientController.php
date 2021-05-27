<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Task;
use PieLab\GAB\Models\StateTask;

/**
 * Class ClientController
 * @package PieLab\GAB\Controllers
 */
class ClientController extends AbstractController
{
    public function __construct()
    {
        parent::__construct("task", Task::class, TopicController::class, "topic", "topic_id");
    }

    /**
     * @OA\Put(
     *   path="/api/task/{task_id}/client_application_state/{state}/",
     *   summary="Set the client application state for the task.",
     *   tags={"Client Application"},
     *   @OA\Parameter(in="path", name="task_id", description="ID of the task to be updated", required=true),
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
      $params = $this->formatParameters(array(
        "id"=>array("default"=>$taskId, "url"=>"task"),
        "state"=>array("default"=>$state, "type"=>StateTask::class, "url"=>"client_application_state")
      ));

      return $this->updateGeneric($params->id, $params);
    }
}
