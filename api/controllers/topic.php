<?php
require_once('controller.php');
require_once('session.php');
require_once('task.php');
require_once('selection.php');
require_once(__DIR__.'/../models/topic.php');

class Topic_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("topic", "Topic", "Session_Controller", "session", "session_id");
  }

  /**
  * @OA\Get(
  *   path="/api/session/{session_id}/topics/",
  *   summary="List of all topics for the session.",
  *   tags={"Topic"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
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
  public function read_all($session_id = null)  {
    return parent::read_all_generic($session_id);
  }

  /**
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
  public function read($id = null)  {
    return parent::read_generic($id);
  }

  /**
  * @OA\Post(
  *   path="/api/session/{session_id}/topic/",
  *   summary="Create a new topic for the session.",
  *   tags={"Topic"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
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
  public function add($session_id = null, $title = null, $description = null)  {
    $params = $this->format_parameters(array(
      "session_id"=>array("default"=>$session_id, "url"=>"session"),
      "title"=>array("default"=>$title),
      "description"=>array("default"=>$description)
    ));

    return $this->add_generic($params->session_id, $params);
  }

  /**
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
  public function update($id = null, $title = null, $description = null, $active_task_id = null)  {
    $params = $this->format_parameters(array(
      "id"=>array("default"=>$id),
      "title"=>array("default"=>$title),
      "description"=>array("default"=>$description),
      "active_task_id"=>array("default"=>$active_task_id)
    ));

    return $this->update_generic($params->id, $params);
  }

  /**
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
  public function delete($id = null)  {
    return parent::delete_generic($id);
  }

  protected function delete_dependencies($id) {
    $query = "SELECT * FROM task WHERE topic_id = :topic_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":topic_id", $id);
    $stmt->execute();

    $result_data = $this->database->fatch_all($stmt);
    $task = Task_Controller::get_instance();
    foreach($result_data as $result_item) {
      $task_id = $result_item["id"];
      $task->delete($task_id);
    }

    $query = "SELECT * FROM selection_group WHERE topic_id = :topic_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":topic_id", $id);
    $stmt->execute();

    $result_data = $this->database->fatch_all($stmt);
    $selection = Selection_Controller::get_instance();
    foreach($result_data as $result_item) {
      $selection_id = $result_item["id"];
      $selection->delete($selection_id);
    }
  }
}
?>
