<?php
require_once('controller.php');

class Voting_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/voting_result/",
  *   summary="Returns the result of the voting for the specified task.",
  *   tags={"Voting"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/VotingResult")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function voting_result()  {
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/voting/{idea_id}/",
  *   summary="Get the task idea voting for the logged in participant.",
  *   tags={"Voting"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\Parameter(in="path", name="idea_id", description="ID of the idea", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Voting"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function get()  {
    $participant_id = getAuthorizationProperty("participant_id");
    #TODO: check rights for session
  }

  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/votings/",
  *   summary="Get all task votings for the logged in participant.",
  *   tags={"Voting"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Voting")),
 *     )
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function get_all()  {
    $participant_id = getAuthorizationProperty("participant_id");
    #TODO: check rights for session
  }


  /**
  * @OA\Post(
  *   path="/api/task/{task_id}/voting/",
  *   summary="Create a new voting for the task.",
  *   tags={"Voting"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Voting")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
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
  *   path="/api/task/{task_id}/voting/",
  *   summary="Update a voting for the task.",
  *   tags={"Voting"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Voting")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function update()  {
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

}
?>
