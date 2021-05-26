<?php
require_once(__DIR__.'/../config/generator.php');
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/session.php');
require_once(__DIR__.'/../models/role.php');
require_once('controller.php');
require_once('topic.php');
require_once('participant.php');

class SessionController extends Controller
{
  public function __construct()
  {
      parent::__construct("session", "Session");
  }

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
  public function readAll() : string {
    $login_id = getAuthorizationProperty("login_id");
    $query = "SELECT * FROM session
      INNER JOIN session_role ON session_role.session_id = session.id
      WHERE session_role.login_id = :login_id";
    /*$query = "SELECT * FROM session
      WHERE id IN (SELECT session_id FROM session_role WHERE login_id = :login_id)";*/
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":login_id", $login_id);
    $stmt->execute();
    $result_data = $this->database->fetchAll($stmt);
    $result = array();
    foreach($result_data as $result_item) {
      array_push($result, new Session($result_item));
    }
    http_response_code(200);
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
  public function read(
      ?string $id = null
  ) : string {
    $login_id = getAuthorizationProperty("login_id");
    if (is_null($id)) {
      $id = $this->getUrlParameter("session", -1);
    }
    $query = "SELECT * FROM session
      INNER JOIN session_role ON session_role.session_id = session.id
      WHERE session.id = :id and session_role.login_id = :login_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":login_id", $login_id);
    $stmt->execute();
    $result = $this->database->fetchFirst($stmt);
    http_response_code(200);
    return json_encode(new Session($result));
  }

  public function readByKey(
      string $session_key
  ) : object {
    $query = "SELECT * FROM session WHERE connection_key = :session_key";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_key", $session_key);
    $stmt->execute();
    $result = (object)$this->database->fetchFirst($stmt, "Wrong Session Key");
    return $result;
  }


  private function generateNewSessionKey() : string {
    $item_count = 1;
    $connection_key = "";
    while ($item_count > 0) {
      $connection_key = keygen(8, false);
      $query = "SELECT id FROM session WHERE connection_key = :key";
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
  public function add(
      ?string $title = null,
      ?string $max_participants = null,
      ?string $expiration_date = null
  ) : string {
    $login_id = getAuthorizationProperty("login_id"); # check if the user is logged in
    $connection_key = $this->generateNewSessionKey();
    $params = $this->formatParameters(array(
      "title"=>array("default"=>$title),
      "connection_key"=>array("default"=>$connection_key),
      "max_participants"=>array("default"=>$max_participants),
      "expiration_date"=>array("default"=>$expiration_date)
    ));

    return $this->addGeneric(null, $params);
  }

  protected function addDependencies(
      string $id,
      array|object|null $parameter
  ) {
    $login_id = getAuthorizationProperty("login_id");
    $role = strtoupper(Role::MODERATOR);
    $query = "INSERT INTO session_role
      (session_id, login_id, role)
      VALUES (:session_id, :login_id, :role)";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $id);
    $stmt->bindParam(":login_id", $login_id);
    $stmt->bindParam(":role", $role);
    $stmt->execute();
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
  *         @OA\Property(property="id", type="string", example="uuid"),
  *         @OA\Property(property="title", type="string"),
  *         @OA\Property(property="max_participants", type="integer", example=100),
  *         @OA\Property(property="expiration_date", type="string", format="date"),
  *         @OA\Property(property="public_screen_module_id", type="string", example=null)
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
    ?string $id=null,
    ?string $title=null,
    ?int $max_participants=null,
    ?string $expiration_date=null,
    ?string $public_screen_module_id=null
  ) : string {
    $params = $this->formatParameters(array(
      "id"=>array("default"=>$id),
      "title"=>array("default"=>$title),
      "max_participants"=>array("default"=>$max_participants),
      "expiration_date"=>array("default"=>$expiration_date),
      "public_screen_module_id"=>array("default"=>$public_screen_module_id)
    ));

    return $this->updateGeneric($params->id, $params);
  }

  /**
   * Checks whether the user is authorised to edit the entry with the specified primary key.
   * @param string|null $id Primary key to be checked.
   * @return string Role with which the user is authorised to access the entry.
   */
  public function checkRights(
      ?string $id
  ) : ?string {
    if (!isParticipant()) {
      $login_id = getAuthorizationProperty("login_id");
      if (is_null($id)) {
        return strtoupper(Role::MODERATOR);
      }
      $query = "SELECT * FROM session_role
        WHERE session_id = :session_id AND login_id = :login_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->bindParam(":login_id", $login_id);
      $stmt->execute();
      $item_count = $stmt->rowCount();
      if ($item_count > 0) {
        $result = $this->database->fetchFirst($stmt);
        return strtoupper($result["role"]);
      }
    }
    else {
      $participant_id = getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM participant
        WHERE session_id = :session_id AND id = :participant_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $id);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->execute();
      $item_count = $stmt->rowCount();
      if ($item_count > 0) {
        $result = $this->database->fetchFirst($stmt);
        return strtoupper(Role::PARTICIPANT);
      }
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
  public function delete(
      ?string $id=null
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
    $query = "SELECT * FROM participant WHERE session_id = :session_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $id);
    $stmt->execute();

    $result_data = $this->database->fetchAll($stmt);
    $participant = ParticipantController::getInstance();
    foreach($result_data as $result_item) {
      $participant_id = $result_item["id"];
      $participant->delete($participant_id);
    }

    $query = "SELECT * FROM topic WHERE session_id = :session_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $id);
    $stmt->execute();

    $result_data = $this->database->fetchAll($stmt);
    $topic = TopicController::getInstance();
    foreach($result_data as $result_item) {
      $topic_id = $result_item["id"];
      $topic->delete($topic_id);
    }

    $query = "DELETE FROM resource WHERE session_id = :session_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $id);
    $stmt->execute();

    $query = "DELETE FROM session_role WHERE session_id = :session_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $id);
    $stmt->execute();
  }
}
?>
