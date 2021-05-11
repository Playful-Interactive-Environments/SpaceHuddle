<?php
require_once('controller.php');
require_once('topic.php');
require_once('idea.php');
require_once(__DIR__.'/../models/task.php');
require_once(__DIR__.'/../models/task_type.php');
require_once(__DIR__.'/../models/state.php');

class Task_Controller extends Controller
{
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
    if (is_null($topic_id)) {
      $topic_id = $this->get_url_parameter("topic");
    }
    $role = Topic_Controller::check_instance_rights($topic_id);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM task ".
      "WHERE topic_id = :topic_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":topic_id", $topic_id);
      $stmt->execute();
      $result_data = $this->database->fatch_all($stmt);
      $result = array();
      foreach($result_data as $result_item) {
        array_push($result, new Task($result_item));
      }
      return json_encode($result);
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read tasks.'
        )
      );
      die($error);
    }
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
    if (is_null($id)) {
      $id = $this->get_url_parameter("task");
    }
    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM task ".
      "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->execute();
      $result = $this->database->fatch_first($stmt);
      return json_encode(new Task($result));
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read tasks.'
        )
      );
      die($error);
    }
  }

  public function check_rights($id) {
    $query = "SELECT * FROM task ".
      "WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fatch_first($stmt);
      $topic_id = $result["topic_id"];
      $role = Topic_Controller::check_instance_rights($topic_id);
      return $role;
    }
    return null;
  }

  public static function check_instance_rights($id) {
    $instance = self::get_instance();
    return $instance->check_rights($id);
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
    if (is_null($topic_id)) {
      $topic_id = $this->get_url_parameter("topic");
    }
    if (is_null($task_type)) {
      $task_type = $this->get_body_parameter("task_type");
    }
    if (is_null($name)) {
      $name = $this->get_body_parameter("name");
    }
    if (is_null($parameter)) {
      $parameter = $this->get_body_parameter("parameter");
    }
    if (is_null($order)) {
      $order = $this->get_body_parameter("order");
    }
    $role = Topic_Controller::check_instance_rights($topic_id);
    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to add a task to this topic.'
          )
        );
        die($error);
    }

    if (isset($task_type)) {
      $task_type = strtoupper($task_type);
    }

    if (isset($task_type) and !defined("Task_Type::$task_type")) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"wrong task type",
            "message"=>"the specified task type does not exist"
          )
        );
        die($error);
    }

    if (isset($parameter))
      $parameter = json_encode((object)$parameter);

    try{
      $this->connection->beginTransaction();
      $state = strtoupper(State_Task::WAIT);
      $id = self::uuid();

      $query = "INSERT INTO task ".
        "(id, topic_id, task_type, name, parameter, `order`, state) ".
        "VALUES (:id, :topic_id, :task_type, :name, :parameter, :order, :state)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":topic_id", $topic_id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->bindParam(":name", $name);
      $stmt->bindParam(":parameter", $parameter);
      $stmt->bindParam(":order", $order);
      $stmt->bindParam(":state", $state);
      $stmt->execute();
      $this->connection->commit();
      $result = $this->read($id);
      return $result;
    }
    catch(Exception $e){
        http_response_code(404);
        $error_msg = $e->getMessage();
        $this->connection->rollBack();
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'Error occurred:'.$error_msg
          )
        );
        die($error);
        #return $error;
    }
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
    if (is_null($id)) {
      $id = $this->get_body_parameter("id");
    }
    if (is_null($task_type)) {
      $task_type = $this->get_body_parameter("task_type");
    }
    if (is_null($name)) {
      $name = $this->get_body_parameter("name");
    }
    if (is_null($parameter)) {
      $parameter = $this->get_body_parameter("parameter");
    }
    if (is_null($order)) {
      $order = $this->get_body_parameter("order");
    }
    if (is_null($state)) {
      $state = $this->get_body_parameter("state");
    }
    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to update this task.'
          )
        );
        die($error);
    }

    if (isset($task_type)) {
      $task_type = strtoupper($task_type);
    }

    if (isset($task_type) and !defined("Task_Type::$task_type")) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"wrong task type",
            "message"=>"the specified task type does not exist"
          )
        );
        die($error);
    }

    if (isset($state)) {
      $state = strtoupper($state);
    }

    if (isset($state) and !defined("State_Task::$state")) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"wrong task state",
            "message"=>"the specified task state does not exist"
          )
        );
        die($error);
    }

    if (isset($parameter))
      $parameter = json_encode((object)$parameter);

    try{
      $this->connection->beginTransaction();

      $query = "UPDATE task SET ".
        "task_type = NVL(:task_type, task_type), ".
        "name = NVL(:name, name), ".
        "parameter = NVL(:parameter, parameter), ".
        "`order` = NVL(:order, `order`), ".
        "state = NVL(:state, state) ".
        "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->bindParam(":name", $name);
      $stmt->bindParam(":parameter", $parameter);
      $stmt->bindParam(":order", $order);
      $stmt->bindParam(":state", $state);
      $stmt->execute();
      $this->connection->commit();
      $result = $this->read($id);
      return $result;
    }
    catch(Exception $e){
        http_response_code(404);
        $error_msg = $e->getMessage();
        $this->connection->rollBack();
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'Error occurred: '.$error_msg
          )
        );
        die($error);
        #return $error;
    }
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
    if (is_null($id)) {
      $id = $this->get_url_parameter("task");
    }
    $role = $this->check_rights($id);

    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to delete this topic.'
          )
        );
        die($error);
        #return $error;
    }

    $handle_transaction = !$this->connection->inTransaction();
    try{
      if ($handle_transaction)
        $this->connection->beginTransaction();

      $query = "SELECT * FROM idea ".
        "WHERE task_id = :task_id ";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_id", $id);
      $stmt->execute();

      $result_data = $this->database->fatch_all($stmt);
      $idea = Idea_Controller::get_instance();
      foreach($result_data as $result_item) {
        $idea_id = $result_item["id"];
        $idea->delete($idea_id);
      }

      $query = "DELETE FROM voting ".
        "WHERE task_id = :task_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_id", $id);
      $stmt->execute();

      $query = "DELETE FROM task ".
        "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->execute();

      if ($handle_transaction)
        $this->connection->commit();
    }
    catch(Exception $e){
        http_response_code(404);
        $error_msg = $e->getMessage();
        $this->connection->rollBack();
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'Error occurred: '.$error_msg
          )
        );
        die($error);
        #return $error;
    }

    return json_encode(
      array(
        "state"=>"Sccess",
        "message"=>"task was successful deleted"
      )
    );
  }
}
?>
