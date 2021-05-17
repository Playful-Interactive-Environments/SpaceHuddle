<?php
require_once('controller.php');
require_once('group.php');
require_once(__DIR__.'/../models/idea.php');

class Group_Idea_Controller extends Controller
{
  public function __construct()
  {
      parent::__construct("hierarchy", "Idea", "Group_Controller", "group", "group_id", "group");
      $this->task_type = Task_Type::GROUPING;
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
  public function read_ideas($group_id = null)  {
    $task_type = strtoupper(Task_Type::BRAINSTORMING);
    $query = "SELECT * FROM idea
      WHERE task_id IN (SELECT id FROM task WHERE task_type like :task_type)
      AND id IN (SELECT sub_idea_id FROM hierarchy WHERE group_idea_id = :group_id)";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":task_type", $task_type);
    return parent::read_all_generic(
      $group_id,
      authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT),
      stmt:  $stmt
    );
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
  *         @OA\Items( type="string", example="uuid")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function add_ideas($group_id = null, $idea_array = null)  {
    $params = $this->format_parameters(array(
      "group_idea_id"=>array("default"=>$group_id, "url"=>"group"),
      "sub_idea_id"=>array("default"=>$idea_array, "type"=>"ARRAY", "result"=>"all")
    ));
    $list = array();
    foreach ($params->sub_idea_id as $key => $value) {
      array_push($list,array(
        "group_idea_id"=>$params->group_idea_id,
        "sub_idea_id"=>$value
      ));
    }
    return $this->add_generic(
      $params->group_idea_id,
      $list,
      insert_id: false,
      duplicate_check: "WHERE NOT EXISTS(
        SELECT 1 FROM hierarchy WHERE group_idea_id = :group_idea_id AND sub_idea_id = :sub_idea_id
        ) LIMIT 1  "
    );
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
  *         @OA\Items( type="string", example="uuid")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete_ideas($group_id = null, $idea_array = null)  {
    $params = $this->format_parameters(array(
      "group_idea_id"=>array("default"=>$group_id, "url"=>"group"),
      "sub_idea_id"=>array("default"=>$idea_array, "type"=>"ARRAY", "result"=>"all")
    ));
    $role = Group_Controller::check_instance_rights($params->group_idea_id);

    $sub_idea_ids = implode(',', $params->sub_idea_id);
    $query = "DELETE FROM hierarchy WHERE group_idea_id = :group_id AND FIND_IN_SET(sub_idea_id, :idea_id) ";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":group_id", $params->group_idea_id);
    $stmt->bindParam(":idea_id", $sub_idea_ids);
    return parent::delete_generic(
      $params->group_idea_id,
      authorized_roles: array(Role::MODERATOR, Role::FACILITATOR),
      stmt: $stmt,
      role: $role
    );
  }
}
?>
