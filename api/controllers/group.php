<?php
require_once('controller.php');
require_once('idea.php');
require_once(__DIR__.'/../models/group.php');

class Group_Controller extends Idea_Controller
{
  public function __construct()
  {
      parent::__construct("idea", "Group", "Task_Controller", "task", "task_id", "group");
      $this->task_type = Task_Type::GROUPING;
  }

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
  public function read_all_from_task($task_id = null, $treat_participants_separately = false)  {
    return parent::read_all_from_task($task_id, $treat_participants_separately);
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/groups/",
  *   summary="List of all groups for the topic.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
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
  public function read_all_from_topic($topic_id = null, $treat_participants_separately = false)  {
    return parent::read_all_from_topic($topic_id, $treat_participants_separately);
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
  public function read($id = null, $treat_participants_separately = false)  {
    return parent::read($id, $treat_participants_separately);
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
  public function add_to_task($task_id = null, $keywords = null, $description = null, $link = null, $image = null)  {
    $state = strtoupper(State_Idea::NEW);
    $params = $this->format_parameters(array(
      "task_id"=>array("default"=>$task_id, "url"=>"task"),
      "keywords"=>array("default"=>$keywords),
      "description"=>array("default"=>$description),
      "link"=>array("default"=>$link),
      "image"=>array("default"=>$image),
      "state"=>array("default"=>$state)
    ));

    return $this->add_generic($params->task_id, $params, authorized_roles: array(Role::MODERATOR, Role::FACILITATOR));
  }

  /**
  * @OA\Post(
  *   path="/api/topic/{topic_id}/group/",
  *   summary="Create a new group for the topic.",
  *   tags={"Group"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
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
  public function add_to_topic($topic_id = null, $keywords = null, $description = null, $link = null, $image = null)  {
    return parent::add_to_topic($topic_id, $keywords, $description, $link, $image);
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
  public function update($id = null, $state = null, $keywords = null, $description = null, $link = null, $image = null)  {
    return parent::update($id, $state, $keywords, $description, $link, $image);
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
  public function delete($id = null)  {
    return parent::delete($id);
  }
}
?>
