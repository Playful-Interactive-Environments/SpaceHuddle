<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\Group;
use PieLab\GAB\Models\TaskType;
use PieLab\GAB\Models\StateIdea;
use PieLab\GAB\Models\Role;

class GroupController extends IdeaController
{
  public function __construct()
  {
      parent::__construct("idea", Group::class, TaskController::class, "task", "task_id", "group");
      $this->task_type = TaskType::GROUPING;
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
  public function readAllFromTask(
      ?string $task_id = null,
      bool $treat_participants_separately = false
  ) : string {
    return parent::readAllFromTask($task_id, $treat_participants_separately);
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
  public function readAllFromTopic(
      ?string $topic_id = null,
      bool $treat_participants_separately = false
  ) : string {
    return parent::readAllFromTopic($topic_id, $treat_participants_separately);
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
  public function read(
      ?string $id = null,
      bool $treat_participants_separately = false
  ) : string {
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
  public function addToTask(
      ?string $task_id = null,
      ?string $keywords = null,
      ?string $description = null,
      ?string $link = null,
      ?string $image = null
  ) : string {
    $state = strtoupper(StateIdea::NEW);
    $params = $this->formatParameters(array(
      "task_id"=>array("default"=>$task_id, "url"=>"task"),
      "keywords"=>array("default"=>$keywords),
      "description"=>array("default"=>$description),
      "link"=>array("default"=>$link),
      "image"=>array("default"=>$image),
      "state"=>array("default"=>$state)
    ));

    return $this->addGeneric($params->task_id, $params, authorizedRoles: array(Role::MODERATOR, Role::FACILITATOR));
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
  public function addToTopic(
      ?string $topic_id = null,
      ?string $keywords = null,
      ?string $description = null,
      ?string $link = null,
      ?string $image = null
  ) : string {
    return parent::addToTopic($topic_id, $keywords, $description, $link, $image);
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
  public function update(
      ?string $id = null,
      ?string $state = null,
      ?string $keywords = null,
      ?string $description = null,
      ?string $link = null,
      ?string $image = null
  ) :string {
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
  public function delete(
      ?string $id = null
  ) :string {
    return parent::delete($id);
  }
}
?>
