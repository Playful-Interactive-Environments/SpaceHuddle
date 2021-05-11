<?php
require_once('controller.php');
require_once('session.php');
require_once('task.php');
require_once('selection.php');
require_once(__DIR__.'/../models/topic.php');

class Topic_Controller extends Controller
{
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
    if (is_null($session_id)) {
      $session_id = $this->get_url_parameter("session");
    }
    $role = Session_Controller::check_instance_rights($session_id);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM topic ".
      "WHERE session_id = :session_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $session_id);
      $stmt->execute();
      $result_data = $this->database->fatch_all($stmt);
      $result = array();
      foreach($result_data as $result_item) {
        array_push($result, new Topic($result_item));
      }
      return json_encode($result);
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read topics.'
        )
      );
      die($error);
    }
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
    if (is_null($id)) {
      $id = $this->get_url_parameter("topic");
    }
    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM topic ".
      "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->execute();
      $result = $this->database->fatch_first($stmt);
      return json_encode(new Topic($result));
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read topics.'
        )
      );
      die($error);
    }
  }

  public function check_rights($id) {
    $query = "SELECT * FROM topic ".
      "WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fatch_first($stmt);
      $session_id = $result["session_id"];
      $role = Session_Controller::check_instance_rights($session_id);
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
    if (is_null($session_id)) {
      $session_id = $this->get_url_parameter("session");
    }
    if (is_null($title)) {
      $title = $this->get_body_parameter("title");
    }
    if (is_null($description)) {
      $description = $this->get_body_parameter("description");
    }
    $role = Session_Controller::check_instance_rights($session_id);
    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to add a topic to this session.'
          )
        );
        die($error);
        #return $error;
    }

    try{
      $this->connection->beginTransaction();
      $id = self::uuid();

      $query = "INSERT INTO topic ".
        " (id, session_id, title, description)".
        " VALUES (:id, :session_id, :title, :description)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":session_id", $session_id);
      $stmt->bindParam(":title", $title);
      $stmt->bindParam(":description", $description);
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
    if (is_null($id)) {
      $id = $this->get_body_parameter("id");
    }
    if (is_null($title)) {
      $title = $this->get_body_parameter("title");
    }
    if (is_null($description)) {
      $description = $this->get_body_parameter("description");
    }
    if (is_null($active_task_id)) {
      $active_task_id = $this->get_body_parameter("active_task_id");
    }
    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to update this topic.'
          )
        );
        die($error);
        #return $error;
    }

    try{
      $this->connection->beginTransaction();

      $query = "UPDATE topic SET ".
        "title = NVL(:title, title), ".
        "description = NVL(:description, description), ".
        "active_task_id = NVL(:active_task_id, active_task_id) ".
        "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":title", $title);
      $stmt->bindParam(":description", $description);
      $stmt->bindParam(":active_task_id", $active_task_id);
      $stmt->bindParam(":id", $id, PDO::PARAM_INT);
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
    if (is_null($id)) {
      $id = $this->get_url_parameter("topic");
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

      $query = "SELECT * FROM task ".
        "WHERE topic_id = :topic_id ";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":topic_id", $id);
      $stmt->execute();

      $result_data = $this->database->fatch_all($stmt);
      $task = Task_Controller::get_instance();
      foreach($result_data as $result_item) {
        $task_id = $result_item["id"];
        $task->delete($task_id);
      }

      $query = "SELECT * FROM selection_group ".
        "WHERE topic_id = :topic_id ";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":topic_id", $id);
      $stmt->execute();

      $result_data = $this->database->fatch_all($stmt);
      $selection = Selection_Controller::get_instance();
      foreach($result_data as $result_item) {
        $selection_id = $result_item["id"];
        $selection->delete($selection_id);
      }

      $query = "DELETE FROM topic ".
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
        "message"=>"topic was successful deleted"
      )
    );
  }
}
?>
