<?php
require_once('Controller.php');
require_once('topic.php');
require_once('idea.php');
require_once(__DIR__.'/../models/task.php');
require_once(__DIR__.'/../models/task_type.php');
require_once(__DIR__.'/../models/state.php');

class TaskController extends Controller
{
  public function __construct()
  {
      parent::__construct("task", "Task", "TopicController", "topic", "topic_id");
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/tasks/",
  *   summary="List of all tasks for the topic.",
  *   tags={"Task"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
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
  public function readAll(
      ?string $topic_id = null
  ) : string {
    return parent::readAllGeneric($topic_id);
  }

  /**
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
  public function read(
      ?string $id = null
  ) : string {
    return parent::readGeneric($id);
  }

  /**
  * @OA\Post(
  *   path="/api/topic/{topic_id}/task/",
  *   summary="Create a new task for the topic.",
  *   tags={"Task"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"id", "task_type"},
  *         @OA\Property(property="task_type", type="string"),
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
      ?string $topic_id = null,
      ?string $task_type = null,
      ?string $name = null,
      ?string $parameter = null,
      ?string $order = null
  ) : string {
    $params = $this->formatParameters(array(
      "topic_id"=>array("default"=>$topic_id, "url"=>"topic"),
      "task_type"=>array("default"=>$task_type, "type"=>"TaskType"),
      "name"=>array("default"=>$name),
      "parameter"=>array("default"=>$parameter, "type"=>"JSON"),
      "order"=>array("default"=>$order),
      "state"=>array("default"=>StateTask::WAIT, "type"=>"StateTask")
    ));

    return $this->addGeneric($params->topic_id, $params);
  }

  /**
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
      ?string $task_type = null,
      ?string $name = null,
      ?string $parameter = null,
      ?string $order = null,
      ?string $state = null
  ) : string {
    $params = $this->formatParameters(array(
      "id"=>array("default"=>$id),
      "task_type"=>array("default"=>$task_type, "type"=>"TaskType"),
      "name"=>array("default"=>$name),
      "parameter"=>array("default"=>$parameter, "type"=>"JSON"),
      "order"=>array("default"=>$order),
      "state"=>array("default"=>$state, "type"=>"StateTask")
    ));

    return $this->updateGeneric($params->id, $params);
  }

  /**
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
  public function delete(
      ?string $id = null
  ) : string {
    return parent::deleteGeneric($id);
  }

  /**
   * Delete dependent data.
   * @param string $id Primary key of the linked table entry.
   */
  protected function deleteDependencies(
      string $id
  ) {
    $query = "SELECT * FROM idea WHERE task_id = :task_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":task_id", $id);
    $stmt->execute();

    $result_data = $this->database->fetchAll($stmt);
    $idea = IdeaController::getInstance();
    foreach($result_data as $result_item) {
      $idea_id = $result_item["id"];
      $idea->delete($idea_id);
    }

    $query = "DELETE FROM voting WHERE task_id = :task_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":task_id", $id);
    $stmt->execute();
  }
}
?>
