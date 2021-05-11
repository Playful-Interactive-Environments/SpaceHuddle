<?php
require_once('controller.php');
require_once('task.php');
require_once('topic.php');
require_once('selection.php');
require_once(__DIR__.'/../models/idea.php');
require_once(__DIR__.'/../models/task_type.php');

class Idea_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/ideas/",
  *   summary="List of all ideas for the task.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Idea")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read_all_from_task($task_id = null)  {
    if (is_null($task_id)) {
      $task_id = $this->get_url_parameter("task");
    }
    $role = Task_Controller::check_instance_rights($task_id);
    $task_type = strtoupper(Task_Type::BRAINSTORMING);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM idea ".
      "WHERE task_id = :task_id ".
      "AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_id", $task_id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->execute();
      $result_data = $this->database->fatch_all($stmt);
      $result = array();
      foreach($result_data as $result_item) {
        array_push($result, new Idea($result_item));
      }
      return json_encode($result);
    }
    elseif (strcasecmp($role, Role::PARTICIPANT) == 0) {
      $participant_id = getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea ".
      "WHERE task_id = :task_id AND participant_id = :participant_id ".
      "AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_id", $task_id);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->execute();
      $result_data = $this->database->fatch_all($stmt);
      $result = array();
      foreach($result_data as $result_item) {
        array_push($result, new Idea($result_item));
      }
      return json_encode($result);
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read ideas.'
        )
      );
      die($error);
    }
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/ideas/",
  *   summary="List of all ideas for the topic.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Idea")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read_all_from_topic($topic_id = null)  {
    if (is_null($topic_id)) {
      $topic_id = $this->get_url_parameter("topic");
    }
    $role = Topic_Controller::check_instance_rights($topic_id);
    $task_type = strtoupper(Task_Type::BRAINSTORMING);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM idea ".
      "WHERE task_id IN (SELECT id FROM task WHERE topic_id = :topic_id AND task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":topic_id", $topic_id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->execute();
      $result_data = $this->database->fatch_all($stmt);
      $result = array();
      foreach($result_data as $result_item) {
        array_push($result, new Idea($result_item));
      }
      return json_encode($result);
    }
    elseif (strcasecmp($role, Role::PARTICIPANT) == 0) {
      $participant_id = getAuthorizationProperty("participant_id");

      $query = "SELECT * FROM idea ".
      "WHERE participant_id = :participant_id ".
      "AND task_id IN (SELECT id FROM task WHERE topic_id = :topic_id AND task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":topic_id", $topic_id);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->execute();
      $result_data = $this->database->fatch_all($stmt);
      $result = array();
      foreach($result_data as $result_item) {
        array_push($result, new Idea($result_item));
      }
      return json_encode($result);
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read ideas.'
        )
      );
      die($error);
    }
  }

  /**
  * @OA\Get(
  *   path="/api/idea/{id}/",
  *   summary="Detail data for the idea with the specified id.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="id", description="ID of idea to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read($id = null)  {
    if (is_null($id)) {
      $id = $this->get_url_parameter("idea");
    }
    $role = $this->check_rights($id);
    $task_type = strtoupper(Task_Type::BRAINSTORMING);
    if (strcasecmp($role, Role::MODERATOR) == 0 or strcasecmp($role, Role::FACILITATOR) == 0) {
      $query = "SELECT * FROM idea ".
      "WHERE id = :id ".
      "AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->execute();
      $result = $this->database->fatch_first($stmt);
      return json_encode(new Idea($result));
    }
    elseif (strcasecmp($role, Role::PARTICIPANT) == 0) {
      $participant_id = getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea ".
      "WHERE id = :id AND participant_id = :participant_id ".
      "AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":task_type", $task_type);
      $stmt->execute();
      $result = $this->database->fatch_first($stmt);
      $item_count = $stmt->rowCount();
      return json_encode(new Idea($result));
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'User is not authorized to read ideas.'
        )
      );
      die($error);
    }
  }

  public function check_rights($id) {
    $query = "SELECT * FROM idea ".
      "WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);

    if (isParticipant()) {
      $participant_id = getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea ".
        "WHERE id = :id AND participant_id = :participant_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":participant_id", $participant_id);
    }

    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fatch_first($stmt);
      $task_id = $result["task_id"];
      $role = Task_Controller::check_instance_rights($task_id);
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
  *   path="/api/task/{task_id}/idea/",
  *   summary="Create a new idea for the task.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"keywords"},
  *         @OA\Property(property="keywords", type="string"),
  *         @OA\Property(property="description", type="string"),
  *         @OA\Property(property="link", type="string"),
  *         @OA\Property(property="image", type="string", format="binary")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add_to_task($task_id = null, $keywords = null, $description = null, $link = null, $image = null)  {
    $participant_id = getAuthorizationProperty("participant_id");
    if (is_null($task_id)) {
      $task_id = $this->get_url_parameter("task");
    }
    if (is_null($keywords)) {
      $keywords = $this->get_body_parameter("keywords");
    }
    if (is_null($description)) {
      $description = $this->get_body_parameter("description");
    }
    if (is_null($link)) {
      $link = $this->get_body_parameter("link");
    }
    if (is_null($image)) {
      $image = $this->get_body_parameter("image");
    }
    $role = Task_Controller::check_instance_rights($task_id);
    if (strcasecmp($role, Role::PARTICIPANT) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to add a idea to this task.'
          )
        );
        die($error);
    }

    try{
      $this->connection->beginTransaction();
      $state = strtoupper(State_Idea::NEW);
      $id = self::uuid();

      $query = "INSERT INTO idea ".
        "(id, task_id, participant_id, state, keywords, description, image, link) ".
        "VALUES (:id, :task_id, :participant_id, :state, :keywords, :description, :image, :link)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":task_id", $task_id);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":state", $state);
      $stmt->bindParam(":keywords", $keywords);
      $stmt->bindParam(":description", $description);
      $stmt->bindParam(":image", $image);
      $stmt->bindParam(":link", $link);
      $stmt->execute();
      $this->connection->commit();
      $result = $this->read($id);
      echo $result;
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
  * @OA\Post(
  *   path="/api/topic/{topic_id}/idea/",
  *   summary="Create a new idea for the topic.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"keywords"},
  *         @OA\Property(property="keywords", type="string"),
  *         @OA\Property(property="description", type="string"),
  *         @OA\Property(property="link", type="string"),
  *         @OA\Property(property="image", type="string", format="binary")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add_to_topic($topic_id = null, $keywords = null, $description = null, $link = null, $image = null)  {
    if (is_null($topic_id)) {
      $topic_id = $this->get_url_parameter("topic");
    }
    $task_type = Task_Type::BRAINSTORMING;

    $query = "SELECT * FROM task ".
      "WHERE topic_id = :topic_id AND task_type like :task_type";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":topic_id", $topic_id);
    $stmt->bindParam(":task_type", $task_type);
    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fatch_first($stmt);
      $task_id = $result["id"];
      $this->add_to_task($task_id, $keywords, $description, $link, $image);
    }
  }


  /**
  * @OA\Put(
  *   path="/api/idea/",
  *   summary="Update a idea.",
  *   tags={"Idea"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Idea")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update($id = null, $state = null, $keywords = null, $description = null, $link = null, $image = null)  {
    $participant_id = getAuthorizationProperty("participant_id");
    if (is_null($id)) {
      $id = $this->get_body_parameter("id");
    }
    if (is_null($state)) {
      $state = $this->get_body_parameter("state");
    }
    if (is_null($keywords)) {
      $keywords = $this->get_body_parameter("keywords");
    }
    if (is_null($description)) {
      $description = $this->get_body_parameter("description");
    }
    if (is_null($link)) {
      $link = $this->get_body_parameter("link");
    }
    if (is_null($image)) {
      $image = $this->get_body_parameter("image");
    }
    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) != 0 and strcasecmp($role, Role::FACILITATOR) != 0 and strcasecmp($role, Role::PARTICIPANT) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to update this idea.'
          )
        );
        die($error);
    }

    if (isset($state)) {
      $state = strtoupper($state);
    }

    if (isset($state) and !defined("State_Idea::$state")) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"wrong idea state",
            "message"=>"the specified idea state does not exist"
          )
        );
        die($error);
    }

    try{
      $this->connection->beginTransaction();

      $query = "UPDATE idea SET ".
        "state = NVL(:state, state), ".
        "keywords = NVL(:keywords, keywords), ".
        "description = NVL(:description, description), ".
        "image = NVL(:image, image), ".
        "link = NVL(:link, link) ".
        "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":state", $state);
      $stmt->bindParam(":keywords", $keywords);
      $stmt->bindParam(":description", $description);
      $stmt->bindParam(":image", $image);
      $stmt->bindParam(":link", $link);
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
  *   path="/api/idea/{id}/",
  *   summary="Delete a idea.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="id", description="ID of idea to delete", required=true),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete($id = null)  {
    if (is_null($id)) {
      $id = $this->get_url_parameter("idea");
    }
    $role = $this->check_rights($id);

    if (strcasecmp($role, Role::MODERATOR) != 0 and strcasecmp($role, Role::FACILITATOR) != 0 and strcasecmp($role, Role::PARTICIPANT) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to delete this idea.'
          )
        );
        die($error);
        #return $error;
    }

    $handle_transaction = !$this->connection->inTransaction();
    try{
      if ($handle_transaction)
        $this->connection->beginTransaction();

      $query = "DELETE FROM voting ".
        "WHERE idea_id = :idea_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":idea_id", $id);
      $stmt->execute();

      $query = "DELETE FROM hierarchy ".
        "WHERE group_idea_id = :idea_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":idea_id", $id);
      $stmt->execute();

      $query = "DELETE FROM hierarchy ".
        "WHERE sub_idea_id = :idea_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":idea_id", $id);
      $stmt->execute();

      $query = "DELETE FROM selection_group_idea ".
        "WHERE idea_id = :idea_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":idea_id", $id);
      $stmt->execute();

      $query = "DELETE FROM random_idea ".
        "WHERE idea_id = :idea_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":idea_id", $id);
      $stmt->execute();

      $query = "DELETE FROM idea ".
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
        "message"=>"idea was successful deleted"
      )
    );
  }

}
?>
