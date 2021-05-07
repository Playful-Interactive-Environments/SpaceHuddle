<?php
require_once('controller.php');

class Topic_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/session/{session_id}/topics/",
  *   summary="List of all topics for the session.",
  *   tags={"Topic"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
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
  public function read_all()  {
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{id}/",
  *   summary="Detail data for the topic with the specified id.",
  *   tags={"Topic"},
  *   @OA\Parameter(in="path", name="id", description="ID of topic to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Topic"),
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
  *   path="/api/session/{session_id}/topic/",
  *   summary="Create a new topic for the session.",
  *   tags={"Topic"},
  *   @OA\Parameter(in="path", name="session_id", description="ID of the session", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"title", "description"},
  *         @OA\Property(property="title", type="string"),
  *         @OA\Property(property="description", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Topic"),
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
  *   path="/api/topic/",
  *   summary="Update a topic.",
  *   tags={"Topic"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Topic")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Topic"),
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
  *   path="/api/topic/{id}/",
  *   summary="Delete a topic.",
  *   tags={"Topic"},
  *   @OA\Parameter(in="path", name="id", description="ID of topic to delete", required=true),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete($id)  {
    #TODO: check rights for session
  }

}
?>
