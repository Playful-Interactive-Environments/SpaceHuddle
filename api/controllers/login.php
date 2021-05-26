<?php
require_once(__DIR__.'/../config/authorization.php');
require_once(__DIR__.'/../models/role.php');
require_once(__DIR__.'/../models/user.php');
require_once('controller.php');
require_once('session.php');

class LoginController extends Controller
{
  public function __construct()
  {
      parent::__construct("login", "User");
  }

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
  public function login(
      ?string $username = null,
      ?string $password = null
  ) : string {
    $params = $this->formatParameters(array(
      "username"=>array("default"=>$username),
      "password"=>array("default"=>$password, "type"=>"MD5")
    ));

    $query = "SELECT * FROM login WHERE username = :username AND password = :password";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":username", $params->username);
    $stmt->bindParam(":password", $params->password);
    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count == 0) {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Login Failed",
          "message"=>"Username or password wrong"
        )
      );
      die($error);
    }
    $result = (object)$this->database->fetchFirst($stmt);
    $jwt = generateToken(array(
        "login_id" => $result->id,
        "username" => $result->username
    ));

    http_response_code(200);
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
  public function register(
      ?string $username = null,
      ?string $password = null,
      ?string $password_confirmation = null
  ) : string {
    $params = $this->formatParameters(array(
      "username"=>array("default"=>$username),
      "password"=>array("default"=>$password, "type"=>"MD5"),
      "password_confirmation"=>array("default"=>$password_confirmation, "type"=>"MD5")
    ));
    if ($this->checkUser($params->username))  {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>'User already exists.'
          )
        );
        die($error);
    }
    $this->checkPasswordConfirmation($params->password, $params->password_confirmation);
    if (isset($params->password_confirmation)) unset($params->password_confirmation);

    $this->addGeneric(null, $params, authorized_roles: array(Role::UNKNOWN));

    http_response_code(200);
    return json_encode(
      array(
        "state"=>"Sccess",
        "message"=>"user was created"
      )
    );
  }

  private function checkPasswordConfirmation(
      string $password,
      string $password_confirmation
  ) {
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

  private function checkUser(
      string $username
  ) : bool {
    $query = "SELECT * FROM login WHERE username = :username";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    $item_count = $stmt->rowCount();

    return ($item_count > 0);
  }

  private function checkPassword(
      string $id,
      string $password
  ) : bool {
    $query = "SELECT * FROM login WHERE id = :id and password = :password";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->bindParam(":password", $password);
    $stmt->execute();
    $item_count = $stmt->rowCount();

    return ($item_count > 0);
  }

  /**
   * Checks whether the user is authorised to edit the entry with the specified primary key.
   * @param string|null $id Primary key to be checked.
   * @return string|null Role with which the user is authorised to access the entry.
   */
  public function checkRights(
      ?string $id
  ) : ?string {
    return $this->checkLogin($id);
  }

  protected function read(
      ?string $id = null
  ) : string {
    return parent::readGeneric($id, role: Role::MODERATOR);
  }

  /**
  * @OA\Put(
  *   path="/api/user/",
  *   summary="Change the password of the logged-in user.",
  *   tags={"User"},
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"old_password", "password", "password_confirmation"},
  *         @OA\Property(property="old_password", type="string"),
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
  public function update(
      ?string $old_password = null,
      ?string $password = null,
      ?string $password_confirmation = null
  ) :string {
    $login_id = getAuthorizationProperty("login_id");
    $params = $this->formatParameters(array(
      "id"=>array("default"=>$login_id),
      "old_password"=>array("default"=>$old_password, "type"=>"MD5"),
      "password"=>array("default"=>$password, "type"=>"MD5"),
      "password_confirmation"=>array("default"=>$password_confirmation, "type"=>"MD5")
    ));
    $this->checkPasswordConfirmation($params->password, $params->password_confirmation);
    if (!$this->checkPassword($login_id, $params->old_password)) {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>"The old password is wrong."
        )
      );
      die($error);
    }
    if (isset($params->old_password))
      unset($params->old_password);
    if (isset($params->password_confirmation))
      unset($params->password_confirmation);

    $this->updateGeneric($params->id, $params);
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
  public function delete() : string {
    $login_id = getAuthorizationProperty("login_id");
    return parent::deleteGeneric($login_id);
  }

  /**
   * Delete dependent data.
   * @param string $id Primary key of the linked table entry.
   */
  protected function deleteDependencies(
      string $id
  ) {
    $role = strtoupper(Role::MODERATOR);
    $query = "SELECT * FROM session_role
      WHERE login_id = :login_id AND role like :role";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":login_id", $id);
    $stmt->bindParam(":role", $role);
    $stmt->execute();

    $result_data = $this->database->fetchAll($stmt);
    foreach($result_data as $result_item) {
      $session_id = $result_item["session_id"];

      $query = "SELECT * FROM session_role
        WHERE session_id = :session_id AND role like :role";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":session_id", $session_id);
      $stmt->bindParam(":role", $role);
      $stmt->execute();
      $item_count = $stmt->rowCount();

      if ($item_count == 1) {
        $session = SessionController::getInstance();
        $session->delete($session_id);
      }
    }

    $query = "DELETE FROM session_role WHERE login_id = :login_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":login_id", $id);
    $stmt->execute();
  }
}
?>
