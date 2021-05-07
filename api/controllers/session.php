<?php
require_once(__DIR__.'/../config/generator.php');
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/session.php');
require_once(__DIR__.'/../models/role.php');
require_once('controller.php');
require_once('topic.php');
require_once('participant.php');

class Session_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/sessions/",
  *   summary="List of all sessions for which the user is authorized.",
  *   tags={"Session"},
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Session")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   @OA\Response(response="401", description="Unauthorized"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read_all()  {
    $login_id = getAuthorizationProperty("login_id");
    $query = "SELECT * FROM session ".
    "INNER JOIN session_role ON session_role.session_id = session.id ".
    "WHERE session_role.login_id = :login_id";
    /*$query = "SELECT * FROM session ".
    "WHERE id IN (SELECT session_id FROM session_role WHERE login_id = :login_id)";*/
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":login_id", $login_id);
    $stmt->execute();
    $result_data = $this->database->fatch_all($stmt);
    $result = array();
    foreach($result_data as $result_item) {
      array_push($result, new Session($result_item));
    }
    return json_encode($result);
  }

  /**
  * @OA\Get(
  *   path="/api/session/{id}/",
  *   summary="Detail data for the session with the specified id.",
  *   tags={"Session"},
  *   @OA\Parameter(in="path", name="id", description="ID of session to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Session"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read($id = null)  {
    $login_id = getAuthorizationProperty("login_id");
    if (is_null($id)) {
      $id = $this->get_url_parameter("session", -1);
    }
    $query = "SELECT * FROM session ".
    "INNER JOIN session_role ON session_role.session_id = session.id ".
    "WHERE session.id = :id and session_role.login_id = :login_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":login_id", $login_id);
    $stmt->execute();
    $result = $this->database->fatch_first($stmt);
    return json_encode(new Session($result));
  }

  public function read_by_key($session_key) {
    $query = "SELECT * FROM session ".
    "WHERE connection_key = :session_key";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_key", $session_key);
    $stmt->execute();
    $result = (object)$this->database->fatch_first($stmt, "Wrong Session Key");
    return $result;
  }


  private function generate_new_session_key() {
    $item_count = 1;
    $connection_key = "";
    while ($item_count > 0) {
      $connection_key = keygen(8, false);
      $query = "SELECT id FROM session".
        " WHERE connection_key = :key";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":key", $connection_key);
      $stmt->execute();
      $item_count = $stmt->rowCount();
    }
    return $connection_key;
  }


  /**
  * @OA\Post(
  *   path="/api/session/",
  *   summary="Create a new session.",
  *   tags={"Session"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"title", "max_participants", "expiration_date"},
  *         @OA\Property(property="title", type="string"),
  *         @OA\Property(property="max_participants", type="integer", example=100),
  *         @OA\Property(property="expiration_date", type="string", format="date")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Session"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add($title = null, $max_participants = null, $expiration_date = null)  {
    $login_id = getAuthorizationProperty("login_id");
    if (is_null($title)) {
      $title = $this->get_body_parameter("title");
    }
    if (is_null($max_participants)) {
      $max_participants = $this->get_body_parameter("max_participants");
    }
    if (is_null($expiration_date)) {
      $expiration_date = $this->get_body_parameter("expiration_date");
    }
    try{
      $this->connection->beginTransaction();
      $connection_key = $this->generate_new_session_key();

      $query = "INSERT INTO session".
        " (title, connection_key, max_participants, expiration_date)".
        " VALUES (:title, :connection_key, :max_participants, :expiration_date)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":title", $title);
      $stmt->bindParam(":connection_key", $connection_key);
      $stmt->bindParam(":max_participants", $max_participants);
      $stmt->bindParam(":expiration_date", $expiration_date);
      $stmt->execute();
      $id = $this->connection->lastInsertId();

      $role = Role::MODERATOR;
      $query = "INSERT INTO session_role".
        " (session_id, login_id, role)".
        " VALUES (:session_id, :login_id, :role)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->bindParam(":login_id", $login_id);
      $stmt->bindParam(":role", $role);
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
  * @OA\Put(
  *   path="/api/session/",
  *   summary="Update a session.",
  *   tags={"Session"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"title", "max_participants", "expiration_date"},
  *         @OA\Property(property="id", type="integer"),
  *         @OA\Property(property="title", type="string"),
  *         @OA\Property(property="max_participants", type="integer", example=100),
  *         @OA\Property(property="expiration_date", type="string", format="date"),
  *         @OA\Property(property="public_screen_module_id", type="string", format="int")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Session"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update(
    $id=null,
    $title=null,
    $max_participants=null,
    $expiration_date=null,
    $public_screen_module_id=null
  ) {
    if (is_null($id)) {
      $id = $this->get_body_parameter("id");
    }
    if (is_null($title)) {
      $title = $this->get_body_parameter("title");
    }
    if (is_null($max_participants)) {
      $max_participants = $this->get_body_parameter("max_participants");
    }
    if (is_null($expiration_date)) {
      $expiration_date = $this->get_body_parameter("expiration_date");
    }
    if (is_null($public_screen_module_id)) {
      $public_screen_module_id = $this->get_body_parameter("public_screen_module_id");
    }

    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to update this session.'
          )
        );
        die($error);
        #return $error;
    }

    try{
      $this->connection->beginTransaction();

      $query = "UPDATE session SET ".
        "title = :title, ".
        "max_participants = :max_participants, ".
        "expiration_date = :expiration_date, ".
        "public_screen_module_id = :public_screen_module_id ".
        "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":title", $title);
      $stmt->bindParam(":max_participants", $max_participants);
      $stmt->bindParam(":expiration_date", $expiration_date);
      $stmt->bindParam(":public_screen_module_id", $public_screen_module_id);
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

  public function check_rights($id) {
    $login_id = getAuthorizationProperty("login_id");
    $query = "SELECT * FROM session_role ".
      "WHERE session_id = :session_id AND login_id = :login_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $id);
    $stmt->bindParam(":login_id", $login_id);
    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fatch_first($stmt);
      return $result["role"];
    }
    return null;
  }

  /**
  * @OA\Delete(
  *   path="/api/session/{id}/",
  *   summary="Delete a session.",
  *   tags={"Session"},
  *   @OA\Parameter(in="path", name="id", description="ID of session to delete", required=true),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete($id=null)  {
    $login_id = getAuthorizationProperty("login_id");
    if (is_null($id)) {
      $id = $this->get_url_parameter("session", -1);
    }

    $role = $this->check_rights($id);
    if (strcasecmp($role, Role::MODERATOR) != 0) {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User is not authorized to delete this session.'
          )
        );
        die($error);
        #return $error;
    }

    try{
      $this->connection->beginTransaction();

      $query = "SELECT * FROM participant ".
        "WHERE session_id = :session_id ";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->execute();

      $result_data = $this->database->fatch_all($stmt);
      foreach($result_data as $result_item) {
        $participant_id = $result_item["id"];
        $participant = Participant_Controller::get_instance();
        $participant->delete($participant_id);
      }

      $query = "SELECT * FROM topic ".
        "WHERE session_id = :session_id ";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->execute();

      $result_data = $this->database->fatch_all($stmt);
      foreach($result_data as $result_item) {
        $topic_id = $result_item["id"];
        $topic = Topic_Controller::get_instance();
        $topic->delete($topic_id);
      }

      $query = "DELETE FROM resource ".
        "WHERE session_id = :session_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->execute();

      $query = "DELETE FROM session_role ".
        "WHERE session_id = :session_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->execute();

      $query = "DELETE FROM session ".
        "WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->execute();

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
        "message"=>"user was successful deleted"
      )
    );
  }
}
?>
