<?php
require_once(__DIR__.'/../config/authorization.php');
require_once('controller.php');

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

    /*
    $core = Core::get_instance();

    $token = array(
       "iat" => $core->issued_at,
       "exp" => $core->expiration_time,
       "iss" => $core->issuer,
       "data" => array(
           "id" => $result->id,
           "username" => $result->username
       )
    );
    // generate jwt
    $jwt = JWT::encode($token, $core->key);*/
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

    $query = "SELECT * FROM login ".
    "WHERE username = :username";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $item_count = $stmt->rowCount();

    if ($item_count > 0)  {
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

      $query = "INSERT INTO login".
        " (username, password)".
        " VALUES (:username, :password)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":username", $username);
      $stmt->bindParam(":password", $password);
      $stmt->execute();
      $id = $this->connection->lastInsertId();
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
    );;
  }

  /**
  * @OA\Put(
  *   path="/api/user/",
  *   summary="Update logged in user.",
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
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update($username = null, $password = null, $password_confirmation = null)  {
    $login_id = getAuthorizationProperty("login_id");
    if (is_null($username)) {
      $username = $this->get_body_parameter("username", "");
    }
    if (is_null($password)) {
      $password = $this->get_body_parameter("password", "");
    }
    if (is_null($password_confirmation)) {
      $password_confirmation = $this->get_body_parameter("password_confirmation", "");
    }
  }

  /**
  * @OA\Delete(
  *   path="/api/user/",
  *   summary="Delete logged in user.",
  *   tags={"User"},
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete()  {
    $login_id = getAuthorizationProperty("login_id");
  }

}
?>
