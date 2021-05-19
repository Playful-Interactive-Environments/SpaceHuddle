<?php
require_once(__DIR__.'/../config/generator.php');
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/avatar.php');
require_once(__DIR__.'/../models/participant.php');
require_once(__DIR__.'/../models/state.php');
require_once('controller.php');
require_once('session.php');

class Participant_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("participant", "Participant", "Session_Controller", "session", "session_id");
  }

  /**
  * @OA\Post(
  *   path="/api/participant/connect/",
  *   summary="connect to a session",
  *   tags={"Participant"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"session_key", "ip_hash"},
  *         @OA\Property(property="session_key", type="string"),
  *         @OA\Property(property="ip_hash", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Participant")),
  *   @OA\Response(response="404", description="Not Found")
  * )
  */
  public function connect($session_key = null, $ip_hash = null)  {
    $params = $this->format_parameters(array(
      "session_key"=>array("default"=>$session_key),
      "ip_hash"=>array("default"=>$ip_hash, "type"=>"MD5")
    ));
    $params->session_id = Session_Controller::get_instance()->read_by_key($params->session_key)->id;

    $result = $this->is_registered($params->session_id, $params->ip_hash);
    if (is_null($result)) {
      $params->browser_key = $this->generate_new_browser_key($params->session_key);
      $params->color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
      $params->symbol = AvatarSymbol::getRandomValue();
      unset($params->session_key);

      $this->add_generic(null, $params, authorized_roles: array(Role::UNKNOWN));
    }

    $jwt = generateToken(array(
        "participant_id" => $result->id,
        "browser_key" => $result->browser_key
    ));
    http_response_code(200);
    return json_encode(
      new Participant((array)$result, $jwt)
    );
  }

  private function generate_new_browser_key($session_key) {
    $item_count = 1;
    $browser_key = "";
    while ($item_count > 0) {
      $browser_key = $session_key.".".keygen(8, false);
      $query = "SELECT id FROM participant WHERE browser_key = :key";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":key", $browser_key);
      $stmt->execute();
      $item_count = $stmt->rowCount();
    }
    return $browser_key;
  }

  protected function read($id)  {
    $query = "SELECT * FROM participant WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = (object)$this->database->fatch_first($stmt);
    return $result;
  }

  public function check_rights($id) {
    return $this->check_login($id);
  }

  protected function is_registered($session_id = null, $ip_hash = null)  {
    $query = "SELECT * FROM participant
      WHERE session_id = :session_id AND ip_hash = :ip_hash";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $session_id);
    $stmt->bindParam(":ip_hash", $ip_hash);
    $stmt->execute();
    $item_count = $stmt->rowCount();

    if ($item_count > 0) {
      return (object)$this->database->fatch_first($stmt);
    }
    return null;
  }

  /**
  * @OA\Get(
  *   path="/api/participant/connect/{browser_key}/",
  *   summary="reconnect to a session",
  *   tags={"Participant"},
  *   @OA\Parameter(in="path", name="browser_key", description="the generated browser_key from the last connection to the session", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Participant")),
  *   @OA\Response(response="404", description="Not Found")
  * )
  */
  public function reconnect($browser_key = null)  {
    if (is_null($browser_key)) {
      $browser_key = $this->get_url_parameter("connect", -1);
    }
    $query = "SELECT * FROM participant WHERE browser_key = :browser_key";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":browser_key", $browser_key);
    $stmt->execute();
    $result = (object)$this->database->fatch_first($stmt);
    $jwt = generateToken(array(
        "participant_id" => $result->id,
        "browser_key" => $result->browser_key
    ));
    $result_obj = new Participant((array)$result, $jwt);
    http_response_code(200);
    return json_encode(
      $result_obj
    );
  }

  /**
  * @OA\Delete(
  *   path="/api/participant/",
  *   summary="Delete logged-in participant.",
  *   tags={"Participant"},
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete()  {
    #TODO: What happens to the ideas and votes submitted by the user? Set id to zero or delete entry?
    #TODO: delete dependent tables
  }

  protected function delete_dependencies($id) {
  }

  /**
  * @OA\Get(
  *   path="/api/participant/tasks/",
  *   summary="List of all tasks for the logged-in participant.",
  *   tags={"Participant"},
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ParticipantTask")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function get_tasks() {
    $active_state = strtoupper(State_Task::ACTIVE);
    if (!isParticipant()) {
      $login_id = getAuthorizationProperty("login_id");
      $query = "SELECT * FROM task
        WHERE state like :active_state
        AND topic_id IN (
          SELECT topic.id
          FROM topic
          INNER JOIN session ON session.id = topic.session_id
          INNER JOIN session_role ON session_role.session_id = session.id
          WHERE session_role.login_id = :login_id
          AND session.expiration_date >= current_timestamp())";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":login_id", $login_id);
      $stmt->bindParam(":active_state", $active_state);
    }
    else {
      $participant_id = getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM task
        WHERE state like :active_state
        AND topic_id IN (
          SELECT topic.id
          FROM topic
          INNER JOIN session ON session.id = topic.session_id
          INNER JOIN participant ON participant.session_id = session.id
          WHERE participant.id = :participant_id and session.expiration_date >= current_timestamp())";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":active_state", $active_state);
    }
    $stmt->execute();
    $result_data = $this->database->fatch_all($stmt);
    $result = array();
    foreach($result_data as $result_item) {
      array_push($result, new Task($result_item));
    }
    http_response_code(200);
    return json_encode($result);
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/participant_tasks/",
  *   summary="List of all topic tasks for the logged-in participant.",
  *   tags={"Participant"},
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
  public function get_topic_tasks() {

  }

  /**
  * @OA\Get(
  *   path="/api/participant/topics/",
  *   summary="List of all topics for the logged-in participant.",
  *   tags={"Participant"},
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
  public function get_topics() {
    if (!isParticipant()) {
      $login_id = getAuthorizationProperty("login_id");
      $query = "SELECT * FROM topic
        WHERE session_id IN (
          SELECT session.id
          FROM session
          INNER JOIN session_role ON session_role.session_id = session.id
          WHERE session_role.login_id = :login_id and session.expiration_date >= current_timestamp())";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":login_id", $login_id);
    }
    else {
      $participant_id = getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM topic
        WHERE session_id IN (
          SELECT session.id
          FROM session
          INNER JOIN participant ON participant.session_id = session.id
          WHERE participant.id = :participant_id and session.expiration_date >= current_timestamp())";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":participant_id", $participant_id);
    }
    $stmt->execute();
    $result_data = $this->database->fatch_all($stmt);
    $result = array();
    foreach($result_data as $result_item) {
      array_push($result, new Topic($result_item));
    }
    http_response_code(200);
    return json_encode($result);
  }

}
?>
