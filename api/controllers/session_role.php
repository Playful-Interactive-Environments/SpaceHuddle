<?php
require_once('controller.php');

class SessionRole_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("session_role", "SessionRole", "Login_Controller", "login", "login_id");
  }

  /**
  * @OA\Get(
  *   path="/api/session/{session_id}/authorized_users/",
  *   summary="List of all authorized users for the session.",
  *   tags={"Session Role"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SessionRole")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read_all()  {
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/session/{session_id}/authorized_users/{username}/",
  *   summary="Get the role of the username in the session.",
  *   tags={"Session Role"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\Parameter(in="path", name="username", description="authorized user", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read($id)  {
    #TODO: check rights for session
  }

  /**
  * @OA\Post(
  *   path="/api/session/{session_id}/authorized_users/",
  *   summary="Add a new authorized user to the session.",
  *   tags={"Session Role"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"username", "role"},
  *         @OA\Property(property="username", type="string"),
  *         @OA\Property(property="role", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add()  {
    try{
      #TODO: check rights for session
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
  *   path="/api/session/{session_id}/authorized_users/",
  *   summary="Update the role of a authorized user for a session.",
  *   tags={"Session Role"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"id", "title", "description"},
  *         @OA\Property(property="username", type="string"),
  *         @OA\Property(property="role", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update($id)  {
    #TODO: check rights for session
  }

  /**
  * @OA\Delete(
  *   path="/api/session/{session_id}/authorized_users/{username}/",
  *   summary="Remove username for a session.",
  *   tags={"Session Role"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\Parameter(in="path", name="username", description="Username of the user who should be deprived of the session permission", required=true),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete($id)  {
    #TODO: check rights for session
  }

  protected function delete_dependencies($id) {
  }

}
?>
