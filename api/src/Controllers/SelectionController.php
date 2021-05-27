<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Selection;

class SelectionController extends AbstractController
{
  public function __construct()
  {
    parent::__construct("selection_group", Selection::class, TopicController::class, "topic", "topic_id", "selection");
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/selections/",
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
  public function readAll(
    ?string $topicId = null
  ) : string {
    return parent::readAllGeneric($topicId);
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
    return parent::readGeneric($id);
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
  public function add(
    ?string $topicId = null,
    ?string $name = null
  ) : string {
    $params = $this->formatParameters(array(
        "topic_id"=>array("default"=>$topicId, "url"=>"topic"),
        "name"=>array("default"=>$name)
    ));

    return $this->addGeneric($params->topic_id, $params);
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
    ?string $id = null,
    ?string $name = null
  ) : string {
    $params = $this->formatParameters(array(
        "id"=>array("default"=>$id),
        "name"=>array("default"=>$name)
    ));

    return $this->updateGeneric($params->id, $params);
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
    return parent::deleteGeneric($id);
  }

  /**
   * Delete dependent data.
   * @param string $id Primary key of the linked table entry.
   */
  protected function deleteDependencies(
    string $id
  ) {
    $query = "DELETE FROM selection_group_idea WHERE selection_group_id = :selection_group_id ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":selection_group_id", $id);
    $stmt->execute();
  }
}
?>
