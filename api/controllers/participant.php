<?php
require_once(__DIR__.'/../config/generator.php');
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/avatar.php');
require_once(__DIR__.'/../models/participant.php');
require_once('controller.php');
require_once('session.php');

class Participant_Controller extends Controller
{
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
    if (is_null($session_key)) {
      $session_key = $this->get_body_parameter("session_key", "");
    }
    if (is_null($ip_hash)) {
      $ip_hash = $this->get_body_parameter("ip_hash", "");
    }
    $session_id = Session_Controller::get_instance()->read_by_key($session_key)->id;
    $ip_hash = md5($ip_hash);

    $query = "SELECT * FROM participant ".
    "WHERE session_id = :session_id AND ip_hash = :ip_hash";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":session_id", $session_id);
    $stmt->bindParam(":ip_hash", $ip_hash);
    $stmt->execute();
    $item_count = $stmt->rowCount();

    if ($item_count == 0) {
      try{
        $this->connection->beginTransaction();
        $browser_key = $this->generate_new_browser_key($session_key);
        $color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
        $symbol = AvatarSymbol::getRandomValue();

        $query = "INSERT INTO participant".
          " (session_id, browser_key, color, symbol, ip_hash)".
          " VALUES (:session_id, :browser_key, :color, :symbol, :ip_hash)";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":browser_key", $browser_key);
        $stmt->bindParam(":color", $color);
        $stmt->bindParam(":symbol", $symbol);
        $stmt->bindParam(":ip_hash", $ip_hash);
        $stmt->execute();
        $id = $this->connection->lastInsertId();
        $this->connection->commit();
        $result = (object)$this->read($id);
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
    else {
      $result = (object)$this->database->fatch_first($stmt);
    }
    $jwt = generateToken(array(
        "participant_id" => $result->id,
        "browser_key" => $result->browser_key
    ));
    return json_encode(
      new Participant((array)$result, $jwt)
    );
  }

  private function generate_new_browser_key($session_key) {
    $item_count = 1;
    $browser_key = "";
    while ($item_count > 0) {
      $browser_key = $session_key.".".keygen(8, false);
      $query = "SELECT id FROM participant".
        " WHERE browser_key = :key";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":key", $browser_key);
      $stmt->execute();
      $item_count = $stmt->rowCount();
    }
    return $browser_key;
  }

  private function read($id)  {
    $query = "SELECT * FROM participant WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = (object)$this->database->fatch_first($stmt);
    return $result;
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
    $query = "SELECT * FROM participant ".
    "WHERE browser_key = :browser_key";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":browser_key", $browser_key);
    $stmt->execute();
    $result = (object)$this->database->fatch_first($stmt);
    $jwt = generateToken(array(
        "participant_id" => $result->id,
        "browser_key" => $result->browser_key
    ));
    $result_obj = new Participant((array)$result, $jwt);
    return json_encode(
      $result_obj
    );
  }

  /**
  * @OA\Delete(
  *   path="/api/participant/",
  *   summary="Delete logged in participant.",
  *   tags={"Participant"},
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete()  {
  }

  /**
  * @OA\Get(
  *   path="/api/participant_tasks/",
  *   summary="List of all tasks for the logged in participant.",
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

  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/participant_tasks/",
  *   summary="List of all topic tasks for the logged in participant.",
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
  *   path="/api/participant_topics/",
  *   summary="List of all topics for the logged in participant.",
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

  }

}
?>
