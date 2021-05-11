<?php
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/role.php');
require_once('controller.php');
require_once('session.php');

/**
 * @OA\Info(title="GAB API", version="0.1")
 * @OA\Schemes(format="https")
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   in="header",
 *   name="Authorization",
 *   type="http",
 *   scheme="Bearer",
 *   bearerFormat="JWT"
 * )
 */
class Login_Controller extends Controller
{
  /**
  * @OA\Post(
  *   path="/api/user/login/",
  *   summary="login with existing user",
  *   tags={"User"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"username", "password"},
  *         @OA\Property(property="username", type="string"),
  *         @OA\Property(property="password", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(
  *         @OA\Property(property="message", type="string"),
  *         @OA\Property(property="access_token", type="string")
  *       )
  *     )),
  *   @OA\Response(response="404", description="Not Found")
  * )
  */
  public function login($username = null, $password = null)  {
    if (is_null($username)) {
      $username = $this->get_body_parameter("username", "");
    }
    if (is_null($password)) {
      $password = $this->get_body_parameter("password", "");
    }
    $password = md5($password);
    $query = "SELECT * FROM login WHERE username = :username AND password = :password";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":password", $password);
    $stmt->execute();
    $result = (object)$this->database->fatch_first($stmt);
    $jwt = generateToken(array(
        "login_id" => $result->id,
        "username" => $result->username
    ));

    return json_encode(
            array(
                "message" => "Successful login.",
                "access_token" => $jwt
            )
        );

  }

  /**
  * @OA\Post(
  *   path="/api/user/register/",
  *   summary="register new user",
  *   tags={"User"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"username", "password", "password_confirmation"},
  *         @OA\Property(property="username", type="string"),
  *         @OA\Property(property="password", type="string"),
  *         @OA\Property(property="password_confirmation", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found")
  * )
  */
  public function register($username = null, $password = null, $password_confirmation = null)  {
    if (is_null($username)) {
      $username = $this->get_body_parameter("username", "");
    }
    if (is_null($password)) {
      $password = $this->get_body_parameter("password", "");
    }
    if (is_null($password_confirmation)) {
      $password_confirmation = $this->get_body_parameter("password_confirmation", "");
    }
    $this->check_password_confirmation($password, $password_confirmation);
    if ($this->check_user($username))  {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User already exists.'
          )
        );
        die($error);
    }

    try{
      $this->connection->beginTransaction();
      $password = md5($password);
      $id = self::uuid();

      $query = "INSERT INTO login".
        " (id, username, password)".
        " VALUES (:id, :username, :password)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":username", $username);
      $stmt->bindParam(":password", $password);
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
        "message"=>"user was created"
      )
    );
  }

  private function check_password_confirmation($password, $password_confirmation) {
    if ($password != $password_confirmation) {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>'Password does not match.'
        )
      );
      die($error);
    }
  }

  private function check_user($username) {
    $query = "SELECT * FROM login ".
    "WHERE username = :username";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $item_count = $stmt->rowCount();

    return ($item_count > 0);
  }

  /**
  * @OA\Put(
  *   path="/api/user/",
  *   summary="Change the password of the logged-in user.",
  *   tags={"User"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"password", "password_confirmation"},
  *         @OA\Property(property="password", type="string"),
  *         @OA\Property(property="password_confirmation", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update($password = null, $password_confirmation = null)  {
    $login_id = getAuthorizationProperty("login_id");
    if (is_null($password)) {
      $password = $this->get_body_parameter("password", "");
    }
    if (is_null($password_confirmation)) {
      $password_confirmation = $this->get_body_parameter("password_confirmation", "");
    }
    $this->check_password_confirmation($password, $password_confirmation);

    try{
      $this->connection->beginTransaction();
      $password = md5($password);

      $query = "UPDATE login SET ".
        "password = :password ".
        "WHERE id = :login_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":password", $password);
      $stmt->bindParam(":login_id", $login_id);
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
        "message"=>"password was successful updated"
      )
    );
  }

  /**
  * @OA\Delete(
  *   path="/api/user/",
  *   summary="Delete the logged-in user.",
  *   tags={"User"},
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete()  {
    $login_id = getAuthorizationProperty("login_id");

    $handle_transaction = !$this->connection->inTransaction();
    try{
      if ($handle_transaction)
        $this->connection->beginTransaction();

      $query = "SELECT * FROM session_role ".
        "WHERE login_id = :login_id AND role like :role";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":login_id", $login_id);
      $stmt->bindParam(":role", Role::MODERATOR);
      $stmt->execute();

      $result_data = $this->database->fatch_all($stmt);
      foreach($result_data as $result_item) {
        $session_id = $result_item["session_id"];

        $query = "SELECT * FROM session_role ".
          "WHERE session_id = :session_id AND role like :role";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":role", Role::MODERATOR);
        $stmt->execute();
        $item_count = $stmt->rowCount();

        if ($item_count == 1) {
          $session = Session_Controller::get_instance();
          $session->delete($session_id);
        }
      }

      $query = "DELETE FROM session_role ".
        "WHERE login_id = :login_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":login_id", $login_id);
      $stmt->execute();

      $query = "DELETE FROM login ".
        "WHERE id = :login_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":login_id", $login_id);
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
        "message"=>"user was successful deleted"
      )
    );
  }

}
?>
