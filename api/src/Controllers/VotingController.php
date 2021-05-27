<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\Voting;
use PieLab\GAB\Models\VotingResult;

class VotingController extends AbstractController
{
  public function __construct()
  {
      parent::__construct("voting", Voting::class, TaskController::class, "task", "task_id");
  }

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
  public function votingResult(string $taskId = null) : string {

    $query = "SELECT idea.id, 
      idea.keywords, 
      idea.description, 
      idea.image, 
      idea.link, 
      SUM(voting.rating) AS rating , 
      SUM(voting.detail_rating) AS detail_rating 
      FROM voting
      INNER JOIN idea ON idea.id = voting.idea_id 
      WHERE voting.task_id = :task_id
      GROUP BY voting.idea_id, voting.task_id";
    $stmt = $this->connection->prepare($query);


    return parent::readAllGenericStmt(
      $taskId,
      [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
      $stmt,
      resultClass: VotingResult::class
    );
  }

  /**
  * @OA\Get(
  *   path="/api/voting/{id}/",
  *   summary="Get the task idea voting for the logged-in participant.",
  *   tags={"Voting"},
  *   @OA\Parameter(in="path", name="id", description="ID of the voting", required=true),
  *   @OA\Response(response="200", description="Success",
  *     @OA\JsonContent(ref="#/components/schemas/Voting"),
  *   ),
  *   @OA\Response(response="404", description="Not Found"),
  *   security={{"api_key": {}}, {"bearerAuth": {}}}
  * )
  */
  public function get(string $id = null) : string {
    $participantId = Authorization::getAuthorizationProperty("participant_id");
    $query = "SELECT * FROM voting
      WHERE id = :id AND participant_id = :participant_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":participant_id", $participantId);
    return parent::readGeneric($id, authorizedRoles: array(Role::PARTICIPANT), statement: $stmt);
  }

  public function read(?string $id = null): string
  {
    return parent::readGeneric($id, authorizedRoles: array(Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT));
  }

  /**
  * @OA\Get(
  *   path="/api/task/{task_id}/votings/",
  *   summary="Get all task votings for the logged-in participant.",
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
  public function getAll(string $taskId = null) : string {
    $participantId = Authorization::getAuthorizationProperty("participant_id");
    $query = "SELECT * FROM voting
      WHERE task_id = :task_id AND participant_id = :participant_id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":participant_id", $participantId);
    return parent::readAllGeneric(
      $taskId,
      authorizedRoles: array(Role::PARTICIPANT),
      statement: $stmt
    );
  }


  /**
  * @OA\Post(
  *   path="/api/task/{task_id}/voting/",
  *   summary="Create a new voting for the task by the logged-in participant.",
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
  public function add(
      ?string $taskId = null,
      ?string $ideaId = null,
      ?string $rating = null,
      ?string $detailRating = null
  ) : string {
    $participantId = Authorization::getAuthorizationProperty("participant_id");
    $params = $this->formatParameters(array(
      "task_id"=>array("default"=>$taskId, "url"=>"task"),
      "idea_id"=>array("default"=>$ideaId),
      "participant_id"=>array("default"=>$participantId),
      "rating"=>array("default"=>$rating),
      "detail_rating"=>array("default"=>$detailRating)
    ));

    return $this->addGeneric($params->task_id, $params, authorizedRoles: array(Role::PARTICIPANT));
  }

  /**
  * @OA\Put(
  *   path="/api/voting/",
  *   summary="Update a vote for the task by the logged-in participant.",
  *   tags={"Voting"},
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
  public function update(
    ?string $id = null,
    ?string $ideaId = null,
    ?string $rating = null,
    ?string $detailRating = null
  ) : string {
    $params = $this->formatParameters(array(
      "id"=>array("default"=>$id),
      "idea_id"=>array("default"=>$ideaId),
      "rating"=>array("default"=>$rating),
      "detail_rating"=>array("default"=>$detailRating)
    ));

    return $this->updateGeneric($params->id, $params, authorizedRoles: array(Role::PARTICIPANT));
  }


  /**
   * @OA\Delete(
   *   path="/api/voting/{id}/",
   *   summary="Delete a voting.",
   *   tags={"Voting"},
   *   @OA\Parameter(in="path", name="id", description="ID of voting to delete", required=true),
   *   @OA\Response(response="200", description="Success"),
   *   @OA\Response(response="404", description="Not Found"),
   *   security={{"api_key": {}}, {"bearerAuth": {}}}
   * )
   */
  public function delete(
    ?string $id = null
  ) : string {
    return parent::deleteGeneric($id, authorizedRoles: array(Role::PARTICIPANT));
  }

  /**
   * Checks whether the user is authorised to edit the entry with the specified primary key.
   * @param string|null $id Primary key to be checked.
   * @return string|null Role with which the user is authorised to access the entry.
   */
  public function checkRights(
    ?string $id
  ) : ?string {
    $query = "SELECT * FROM voting
      WHERE id = :id";
    $stmt = $this->connection->prepare($query);
    $stmt->bindParam(":id", $id);

    if (Authorization::isParticipant()) {
      $participantId = Authorization::getAuthorizationProperty("participant_id");
      $query = "SELECT * FROM voting
        WHERE id = :id AND participant_id = :participant_id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->bindParam(":participant_id", $participantId);
    }

    $stmt->execute();
    $item_count = $stmt->rowCount();
    if ($item_count > 0) {
      $result = $this->database->fetchFirst($stmt);
      $taskId = $result["task_id"];
      $role = TaskController::checkInstanceRights($taskId);
      return $role;
    }
    return null;
  }
}
?>
