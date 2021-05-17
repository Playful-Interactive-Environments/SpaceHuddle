<?php
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/task.php');
require_once('controller.php');

class Public_Screen_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("task", "Task", "Topic_Controller", "topic", "topic_id");
  }

  /**
  * @OA\Post(
  *   path="/api/session/{session_id}/public_screen/",
  *   summary="Set a task to be displayed on the public screen for the session.",
  *   tags={"Public Screen"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session to be updated", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"task_id"},
  *         @OA\Property(property="task_id", type="string", format="int")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function set_public_screen($session_id, $task_id)  {
    $login_id = getAuthorizationProperty("login_id");
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/session/{session_id}/public_screen/",
  *   summary="Get the aktive task to be displayed on the public screen for the session.",
  *   tags={"Public Screen"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session to be displayed", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Task"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function get_public_screen($session_id, $task_id)  {
    $login_id = getAuthorizationProperty("login_id");
    #TODO: check rights for session
  }
}
?>
