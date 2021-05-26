<?php
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/task.php');
require_once('controller.php');

class ClientController extends Controller
{
  public function __construct()
  {
      parent::__construct("task", "Task", "TopicController", "topic", "topic_id");
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
  *     @OA\Schema(ref="#/components/schemas/StateTask")),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function setClient(
      ?string $session_id = null,
      ?string $task_id = null
  ) : mixed {
    $login_id = getAuthorizationProperty("login_id");
    #TODO: check rights for session
  }
}
?>
