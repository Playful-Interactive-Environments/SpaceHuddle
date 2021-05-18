<?php
require_once('controller.php');
require_once('topic.php');
require_once('idea.php');
require_once(__DIR__.'/../models/task.php');
require_once(__DIR__.'/../models/task_type.php');
require_once(__DIR__.'/../models/state.php');

class Task_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("task", "Task", "Topic_Controller", "topic", "topic_id");
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
  public function read_all($topic_id = null)  {
    return parent::read_all_generic($topic_id);
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
  public function read($id = null)  {
    return parent::read_generic($id);
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
  public function add($topic_id = null, $task_type = null, $name = null, $parameter = null, $order = null)  {
    $params = $this->format_parameters(array(
      "topic_id"=>array("default"=>$topic_id, "url"=>"topic"),
      "task_type"=>array("default"=>$task_type, "type"=>"Task_Type"),
      "name"=>array("default"=>$name),
      "parameter"=>array("default"=>$parameter, "type"=>"JSON"),
      "order"=>array("default"=>$order),
      "state"=>array("default"=>State_Task::WAIT, "type"=>"State_Task")
    ));

    return $this->add_generic($params->topic_id, $params);
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
  public function update($id = null, $task_type = null, $name = null, $parameter = null, $order = null, $state = null)  {
    $params = $this->format_parameters(array(
      "id"=>array("default"=>$id),
      "task_type"=>array("default"=>$task_type, "type"=>"Task_Type"),
      "name"=>array("default"=>$name),
      "parameter"=>array("default"=>$parameter, "type"=>"JSON"),
      "order"=>array("default"=>$order),
      "state"=>array("default"=>$state, "type"=>"State_Task")
    ));

    return $this->update_generic($params->id, $params);
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
  public function delete($id = null)  {
    return parent::delete_generic($id);
  }

  protected function delete_dependencies($id) {
    $query = "SELECT * FROM idea WHERE task_id = :task_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":task_id", $id);
    $stmt->execute();

    $result_data = $this->database->fatch_all($stmt);
    $idea = Idea_Controller::get_instance();
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
