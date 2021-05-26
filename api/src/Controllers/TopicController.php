<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Topic;

#require_once('task.php');

class TopicController extends Controller
{
  public function __construct()
  {
      parent::__construct("topic", Topic::class, SessionController::class, "session", "session_id");
  }

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
  public function readAll(
      ?string $session_id = null
  ) : string {
    return parent::readAllGeneric($session_id);
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
  public function read(
      ?string $id = null
  ) : string {
    return parent::readGeneric($id);
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
  public function add(
      ?string $session_id = null,
      ?string $title = null,
      ?string $description = null
  ) : string {
    $params = $this->formatParameters(array(
      "session_id"=>array("default"=>$session_id, "url"=>"session"),
      "title"=>array("default"=>$title),
      "description"=>array("default"=>$description)
    ));

    return $this->addGeneric($params->session_id, $params);
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
  public function update(
      ?string $id = null,
      ?string $title = null,
      ?string $description = null,
      ?string $active_task_id = null
  ) : string {
    $params = $this->formatParameters(array(
      "id"=>array("default"=>$id),
      "title"=>array("default"=>$title),
      "description"=>array("default"=>$description),
      "active_task_id"=>array("default"=>$active_task_id)
    ));

    return $this->updateGeneric($params->id, $params);
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
  public function delete(
      ?string $id = null
  ) : string {
    return parent::deleteGeneric($id);
  }

  /**
   * Delete dependent data.
   * @param string $id Primary key of the linked table entry.
   */
  protected function deleteDependencies(
      string $id
  ) {
    $query = "SELECT * FROM task WHERE topic_id = :topic_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":topic_id", $id);
    $stmt->execute();

    $result_data = $this->database->fetchAll($stmt);
    $task = TaskController::getInstance();
    foreach($result_data as $result_item) {
      $task_id = $result_item["id"];
      $task->delete($task_id);
    }

    $query = "SELECT * FROM selection_group WHERE topic_id = :topic_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":topic_id", $id);
    $stmt->execute();

    $result_data = $this->database->fetchAll($stmt);
    $selection = SelectionController::getInstance();
    foreach($result_data as $result_item) {
      $selection_id = $result_item["id"];
      $selection->delete($selection_id);
    }
  }
}
?>
