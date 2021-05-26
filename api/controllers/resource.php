<?php
require_once('controller.php');

class ResourceController extends Controller
{
  public function __construct()
  {
      parent::__construct("resource", "Resource", "SessionController", "session", "session_id");
  }

  /**
  * @OA\Get(
  *   path="/api/session/{session_id}/resources/",
  *   summary="List of all resources for the session.",
  *   tags={"Resource"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Resource"))
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function readAll() : string {
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/resource/{id}/",
  *   summary="Detail data for the resource with the specified id.",
  *   tags={"Resource"},
  *   @OA\Parameter(in="path", name="id", description="ID of resource to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Resource"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read(
      ?string $id = null
  ) : string {
    #TODO: check rights for session
  }

  /**
  * @OA\Post(
  *   path="/api/session/{session_id}/resource/",
  *   summary="Create a new resource for the session.",
  *   tags={"Resource"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"title"},
  *         @OA\Property(property="title", type="string"),
  *         @OA\Property(property="link", type="string"),
  *         @OA\Property(property="image", type="string", format="binary")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Resource"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add() : string {
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
  *   path="/api/resource/",
  *   summary="Update a resource.",
  *   tags={"Resource"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Resource")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Resource"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update(
      ?string  $id = null
  ) : string {
    #TODO: check rights for session
  }

  /**
  * @OA\Delete(
  *   path="/api/resource/{id}/",
  *   summary="Delete a resource.",
  *   tags={"Resource"},
  *   @OA\Parameter(in="path", name="id", description="ID of resource to delete", required=true),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete(
      ?string $id = null
  ) : string {
    #TODO: check rights for session
  }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(
        string $id
    ) {

    }

}
?>
