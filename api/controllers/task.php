<?php
require_once('controller.php');

class Task_Controller extends Controller
{
  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/tasks/",
  *   summary="List of all tasks for the topic.",
  *   tags={"Task"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\MediaType(
 *         mediaType="application/json",
 *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Task")),
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
  *   path="/api/task/{id}/",
  *   summary="Detail data for the task with the specified id.",
  *   tags={"Task"},
  *   @OA\Parameter(in="path", name="id", description="ID of task to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Task"),
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
  *   path="/api/topic/{topic_id}/task/",
  *   summary="Create a new task for the topic.",
  *   tags={"Task"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"id", "task_type"},
  *         @OA\Property(property="task_type", type="string"),
  *         @OA\Property(property="name", type="string"),
  *         @OA\Property(property="parameter", type="object", format="json"),
  *         @OA\Property(property="order", type="integer")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Task"),
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
  *   path="/api/task/",
  *   summary="Update a task.",
  *   tags={"Task"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Task")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Task"),
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
  *   path="/api/task/{id}/",
  *   summary="Delete a task.",
  *   tags={"Task"},
  *   @OA\Parameter(in="path", name="id", description="ID of task to delete", required=true),
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
