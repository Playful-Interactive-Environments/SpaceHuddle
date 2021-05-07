<?php
require_once('controller.php');

class Selection_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/selection/",
  *   summary="List of all selections for the topic.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SelectionGroup")),
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
  *   path="/api/selection/{id}/",
  *   summary="Detail data for the selection with the specified id.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="id", description="ID of selection to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/SelectionGroup"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read($id)  {
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/selection/{selection_id}/ideas",
  *   summary="Ideas for the selection with the specified id.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="selection_id", description="ID of selection to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Idea")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read_ideas($id)  {
    #TODO: check rights for session
  }

  /**
  * @OA\Post(
  *   path="/api/topic/{topic_id}/selection/",
  *   summary="Create a new selection for the topic.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"name"},
  *         @OA\Property(property="name", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/SelectionGroup"),
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
  * @OA\Post(
  *   path="/api/selection/{selection_id}/ideas/",
  *   summary="Add list of idea_ids to a selection.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="selection_id", description="ID of the selection", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(type="array",
  *         @OA\Items( type="integer")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add_ideas()  {
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
  *   path="/api/selection/",
  *   summary="Update a selection.",
  *   tags={"Selection"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/SelectionGroup")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/SelectionGroup"),
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
  *   path="/api/selection/{selection_id}/ideas/",
  *   summary="Delete the list of idea_ids from a selection.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="selection_id", description="ID of the selection", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(type="array",
  *         @OA\Items( type="integer")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete_ideas()  {
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
  * @OA\Delete(
  *   path="/api/selection/{id}/",
  *   summary="Delete a selection.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="id", description="ID of selection to delete", required=true),
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
