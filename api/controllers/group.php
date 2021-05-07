<?php
require_once('controller.php');

class Group_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/groups/",
  *   summary="List of all groups for the task.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Group")),
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
  *   path="/api/group/{id}/",
  *   summary="Detail data for the group with the specified id.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="id", description="ID of group to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Group"),
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
  *   path="/api/group/{group_id}/ideas",
  *   summary="Ideas for the group with the specified id.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="group_id", description="ID of group to return", required=true),
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
  *   path="/api/task/{task_id}/group/",
  *   summary="Create a new group for the task.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"keywords"},
  *         @OA\Property(property="keywords", type="string"),
  *         @OA\Property(property="description", type="string")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Group"),
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
  *   path="/api/group/{group_id}/ideas/",
  *   summary="Add list of idea_ids to a group.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="group_id", description="ID of the group", required=true),
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
  *   path="/api/group/",
  *   summary="Update a group.",
  *   tags={"Group"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Group")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Group"),
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
  *   path="/api/group/{group_id}/ideas/",
  *   summary="Delete the list of idea_ids from a group.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="group_id", description="ID of the group", required=true),
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
  *   path="/api/group/{id}/",
  *   summary="Delete a group.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="id", description="ID of group to delete", required=true),
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
