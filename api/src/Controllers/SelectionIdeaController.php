<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\Idea;
use PieLab\GAB\Models\TaskType;

class SelectionIdeaController extends Controller
{
  public function __construct()
  {
    parent::__construct("selection_group_idea", Idea::class, SelectionController::class,
      "selection", "selection_group_id", "selection");
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
    ?string $selectionGroupId = null
  ) : string {
    $taskType = strtoupper(TaskType::BRAINSTORMING);
    $query = "SELECT * FROM idea
      WHERE task_id IN (SELECT id FROM task WHERE task_type like :task_type)
      AND id IN (SELECT idea_id FROM selection_group_idea WHERE selection_group_id = :selection_group_id)";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":task_type", $taskType);
    return parent::readAllGeneric(
      $selectionGroupId,
      authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT),
      stmt: $stmt
    );
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
  public function addIdeas(
    ?string $selectionId = null,
    ?array $ideaArray = null
  ) : string {
    $params = $this->formatParameters(array(
      "selection_group_id"=>array("default"=>$selectionId, "url"=>"selection"),
      "idea_id"=>array("default"=>$ideaArray, "type"=>"ARRAY", "result"=>"all")
    ));
    $list = array();
    foreach ($params->idea_id as $key => $value) {
      array_push($list,array(
        "selection_group_id"=>$params->selection_group_id,
        "idea_id"=>$value
      ));
    }
    return $this->addGeneric(
      $params->selection_group_id,
      $list,
      insert_id: false,
      duplicate_check: "WHERE NOT EXISTS(
        SELECT 1 FROM selection_group_idea WHERE selection_group_id = :selection_group_id AND idea_id = :idea_id
        ) LIMIT 1  "
    );
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
  public function deleteIdeas(
    ?string $selectionId = null,
    array $ideaArray = null
  ) : string {
    $params = $this->formatParameters(array(
        "selection_group_id"=>array("default"=>$selectionId, "url"=>"selection"),
        "idea_id"=>array("default"=>$ideaArray, "type"=>"ARRAY", "result"=>"all")
    ));

    $idea_ids = implode(',', $params->idea_id);
    $query = "DELETE FROM selection_group_idea WHERE selection_group_id = :selection_group_id AND FIND_IN_SET(idea_id, :idea_id) ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":selection_group_id", $params->selection_group_id);
    $stmt->bindParam(":idea_id", $idea_ids);

    return parent::deleteGeneric(
      $params->selection_group_id,
      authorized_roles: array(Role::MODERATOR, Role::FACILITATOR),
      stmt: $stmt
    );
  }

  /**
   * Checks whether the user is authorised to edit the entry with the specified primary key.
   * @param string|null $id Primary key to be checked.
   * @return string|null Role with which the user is authorised to access the entry.
   */
  public function checkRights(
    ?string $id
  ) : ?string {
    return SelectionController::checkInstanceRights($id);
  }
}
?>
