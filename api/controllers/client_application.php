<?php
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/task.php');
require_once('controller.php');

class Client_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("task", "Task", "Topic_Controller", "topic", "topic_id");
  }
  
  /**
  * @OA\Post(
  *   path="/api/task/{task_id}/client_application/{state}/",
  *   summary="Set the client application state for the task.",
  *   tags={"Client Application"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task to be updated", required=true),
  *   @OA\Parameter(in="path", name="state",
  *     description="display status on the client devices",
  *     required=true,
  *     @OA\Schema(ref="#/components/schemas/State_Task")),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function set_client($session_id, $task_id)  {
    $login_id = getAuthorizationProperty("login_id");
    #TODO: check rights for session
  }
}
?>
