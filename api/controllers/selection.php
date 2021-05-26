<?php
require_once('controller.php');

class SelectionController extends Controller
{
  public function __construct()
  {
      parent::__construct("selection_group", "Selection", "IdeaController", "idea", "idea_id");
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/selection/",
  *   summary="List of all selections for the topic.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Selection")),
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
  *   path="/api/selection/{id}/",
  *   summary="Detail data for the selection with the specified id.",
  *   tags={"Selection"},
  *   @OA\Parameter(in="path", name="id", description="ID of selection to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Selection"),
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
  public function readIdeas(
      ?string $id = null
  ) : string {
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
  *     @OA\JsonContent(ref="#/components/schemas/Selection"),
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
  public function addIdeas() : string {
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
  *         @OA\Schema(ref="#/components/schemas/Selection")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Selection"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update(
      ?string $id = null
  ) : string {
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
  public function deleteIdeas() : string {
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
