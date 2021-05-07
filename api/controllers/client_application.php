<?php
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/task.php');
require_once('controller.php');

class Client_Controller extends Controller
{
  /**
  * @OA\Post(
  *   path="/api/task/{task_id}/client_application/",
  *   summary="Set a client application state for the task.",
  *   tags={"Client Application"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task to be updated", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"state"},
  *         @OA\Property(property="state", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function set_client($session_id, $task_id)  {
    $login_id = getAuthorizationProperty("login_id");
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/client_application/",
  *   summary="Get the aktive task to be displayed on the client application for the topic.",
  *   tags={"Client Application"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic to be displayed", required=true),
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
  public function get_client($session_id, $task_id)  {
    $login_id = getAuthorizationProperty("login_id");
    #TODO: check rights for session
    #TODO: only task with state "ACTIVE" or "READ_ONLY"
  }
}
?>
