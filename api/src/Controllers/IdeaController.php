<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Idea;
use PieLab\GAB\Models\TaskType;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\StateIdea;

class IdeaController extends Controller
{
  protected $task_type = TaskType::BRAINSTORMING;

  public function __construct($table = null, $class = null, $parent_controller = null, $parent_table = null, $parent_id_name = null, $url_parameter = null)
  {
      if (is_null($table)) $table = "idea";
      if (is_null($class)) $class = Idea::class;
      if (is_null($parent_controller)) $parent_controller = TaskController::class;
      if (is_null($parent_table)) $parent_table = "task";
      if (is_null($parent_id_name)) $parent_id_name = "task_id";
      parent::__construct($table, $class, $parent_controller, $parent_table, $parent_id_name, $url_parameter);
      $this->task_type = TaskType::BRAINSTORMING;
  }

  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/ideas/",
  *   summary="List of all ideas for the task.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
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
  public function readAllFromTask(
      ?string $task_id = null,
      bool $treat_participants_separately = true
  ) : string {
    $task_type = strtoupper($this->task_type);
    if (!Authorization::isParticipant() or !$treat_participants_separately) {
      $query = "SELECT * FROM idea
        WHERE task_id = :task_id
        AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_type", $task_type);
    }
    else {
      $participant_id = Authorization::getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea
        WHERE task_id = :task_id AND participant_id = :participant_id
        AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":task_type", $task_type);
    }
    return parent::readAllGeneric($task_id, authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT), stmt:  $stmt);
  }

  /**
  * @OA\Get(
  *   path="/api/topic/{topic_id}/ideas/",
  *   summary="List of all ideas for the topic.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
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
  public function readAllFromTopic(
      ?string $topic_id = null,
      bool $treat_participants_separately = true
  ) :string {
    $task_type = strtoupper($this->task_type);
    if (!Authorization::isParticipant() or !$treat_participants_separately) {
      $query = "SELECT * FROM idea
        WHERE task_id IN (SELECT id FROM task WHERE topic_id = :topic_id AND task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_type", $task_type);
    }
    else {
      $participant_id = Authorization::getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea
        WHERE participant_id = :participant_id
        AND task_id IN (SELECT id FROM task WHERE topic_id = :topic_id AND task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":task_type", $task_type);
    }
    return parent::readAllGeneric(
      $topic_id,
      authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT),
      stmt:  $stmt,
      parent_table: "topic",
      parent_id_name: "topic_id",
      parent_controller: TopicController::class
    );
  }

  /**
  * @OA\Get(
  *   path="/api/idea/{id}/",
  *   summary="Detail data for the idea with the specified id.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="id", description="ID of idea to return", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function read(
      ?string $id = null,
      bool $treat_participants_separately = true
  ) : string {
    $task_type = strtoupper($this->task_type);
    if (!Authorization::isParticipant() or !$treat_participants_separately) {
      $query = "SELECT * FROM idea
        WHERE id = :id
        AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":task_type", $task_type);
    }
    else {
      $participant_id = Authorization::getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea
        WHERE id = :id AND participant_id = :participant_id
        AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":participant_id", $participant_id);
      $stmt->bindParam(":task_type", $task_type);
    }
    return parent::readGeneric($id, authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT), stmt:  $stmt);

    /*$query = "SELECT * FROM idea
      WHERE id = :id
      AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":task_type", $task_type);
    return parent::read_generic($id, authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT), stmt:  $stmt);
    */
  }

  /**
   * Checks whether the user is authorised to edit the entry with the specified primary key.
   * @param string|null $id Primary key to be checked.
   * @return string|null Role with which the user is authorised to access the entry.
   */
  public function checkRights(
      ?string $id
  ) : ?string {
    $query = "SELECT * FROM idea
      WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);

    if (Authorization::isParticipant()) {
      $participant_id = Authorization::getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM idea
        WHERE id = :id AND participant_id = :participant_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":participant_id", $participant_id);
    }

    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fetchFirst($stmt);
      $task_id = $result["task_id"];
      $role = TaskController::checkInstanceRights($task_id);
      return $role;
    }
    return null;
  }

  /**
   * Checks whether the user is authorised to read the entry with the specified primary key.
   * @param string|null $id Primary key to be checked.
   * @return string|null Role with which the user is authorised to access the entry.
   */
  public function checkReadRights(
      ?string $id
  ) : ?string {
    return parent::checkRights($id);
  }

  /**
  * @OA\Post(
  *   path="/api/task/{task_id}/idea/",
  *   summary="Create a new idea for the task.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="task_id", description="ID of the task", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"keywords"},
  *         @OA\Property(property="keywords", type="string"),
  *         @OA\Property(property="description", type="string"),
  *         @OA\Property(property="link", type="string"),
  *         @OA\Property(property="image", type="string", format="binary")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
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
    $participant_id = Authorization::getAuthorizationProperty("participant_id");
    $state = strtoupper(StateIdea::NEW);
    $params = $this->formatParameters(array(
      "task_id"=>array("default"=>$task_id, "url"=>"task"),
      "keywords"=>array("default"=>$keywords),
      "description"=>array("default"=>$description),
      "link"=>array("default"=>$link),
      "image"=>array("default"=>$image),
      "state"=>array("default"=>$state),
      "participant_id"=>array("default"=>$participant_id)
    ));

    return $this->addGeneric($params->task_id, $params, authorized_roles: array(Role::PARTICIPANT));
  }

  /**
  * @OA\Post(
  *   path="/api/topic/{topic_id}/idea/",
  *   summary="Create a new idea for the topic.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
  *   @OA\RequestBody(
  *     @OA\MediaType(
  *       mediaType="json",
  *       @OA\Schema(required={"keywords"},
  *         @OA\Property(property="keywords", type="string"),
  *         @OA\Property(property="description", type="string"),
  *         @OA\Property(property="link", type="string"),
  *         @OA\Property(property="image", type="string", format="binary")
  *       )
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
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
    if (is_null($topic_id)) {
      $topic_id = $this->getUrlParameter("topic");
    }
    $task_type = strtoupper($this->task_type);

    $query = "SELECT * FROM task
      WHERE topic_id = :topic_id AND task_type like :task_type";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":topic_id", $topic_id);
    $stmt->bindParam(":task_type", $task_type);
    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fetchFirst($stmt);
      $task_id = $result["id"];
      return $this->addToTask($task_id, $keywords, $description, $link, $image);
    }
  }


  /**
  * @OA\Put(
  *   path="/api/idea/",
  *   summary="Update a idea.",
  *   tags={"Idea"},
  *   @OA\RequestBody(
  *     required=true,
  *     @OA\MediaType(
  *         mediaType="json",
  *         @OA\Schema(ref="#/components/schemas/Idea")
  *     )
  *   ),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Idea"),
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
  ) : string {
    $params = $this->formatParameters(array(
      "id"=>array("default"=>$id),
      "keywords"=>array("default"=>$keywords),
      "description"=>array("default"=>$description),
      "link"=>array("default"=>$link),
      "image"=>array("default"=>$image),
      "state"=>array("default"=>$state, "type"=>StateIdea::class)
    ));

    return $this->updateGeneric($params->id, $params, authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT));
  }

  /**
  * @OA\Delete(
  *   path="/api/idea/{id}/",
  *   summary="Delete a idea.",
  *   tags={"Idea"},
  *   @OA\Parameter(in="path", name="id", description="ID of idea to delete", required=true),
  *   @OA\Response(response="200", description="Success"),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function delete(
      ?string $id = null
  ) : string {
    return parent::deleteGeneric($id, authorized_roles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT));
  }

  /**
   * Delete dependent data.
   * @param string $id Primary key of the linked table entry.
   */
  protected function deleteDependencies(
      string $id
  ) {
    $query = "DELETE FROM voting WHERE idea_id = :idea_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":idea_id", $id);
    $stmt->execute();

    $query = "DELETE FROM hierarchy WHERE group_idea_id = :idea_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":idea_id", $id);
    $stmt->execute();

    $query = "DELETE FROM hierarchy WHERE sub_idea_id = :idea_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":idea_id", $id);
    $stmt->execute();

    $query = "DELETE FROM selection_group_idea WHERE idea_id = :idea_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":idea_id", $id);
    $stmt->execute();

    $query = "DELETE FROM random_idea WHERE idea_id = :idea_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":idea_id", $id);
    $stmt->execute();
  }

}
?>
