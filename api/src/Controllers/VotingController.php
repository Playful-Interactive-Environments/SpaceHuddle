<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\Voting;
use PieLab\GAB\Models\VotingResult;

/**
 * Controller for voting.
 * @package PieLab\GAB\Controllers
 */
class VotingController extends AbstractController
{
    /**
     * Create a new VotingController.
     */
    protected function __construct()
    {
        parent::__construct("voting", Voting::class, TaskController::class, "task", "task_id");
    }

    /**
     * Returns the result of the voting for the specified task.
     * @param string|null $taskId The task's ID.
     * @return string The voting result in JSON format.
     * @OA\Get(
     *   path="/api/task/{taskId}/voting_result/",
     *   summary="Returns the result of the voting for the specified task.",
     *   tags={"Voting"},
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
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
    public function votingResult(string $taskId = null): string
    {
        $query = "SELECT idea.id, idea.keywords, idea.description, idea.image, idea.link, SUM(voting.rating) AS rating,
                  SUM(voting.detail_rating) AS detail_rating FROM voting INNER JOIN idea ON idea.id = voting.idea_id
                  WHERE voting.task_id = :task_id GROUP BY voting.idea_id, voting.task_id";
        $statement = $this->connection->prepare($query);

        return parent::readAllGenericStmt(
            $taskId,
            [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            $statement,
            resultClass: VotingResult::class
        );
    }

    /**
     * Get the task idea voting for the logged-in participant.
     * @param string|null $id The task ID.
     * @return string The voting data in JSON format.
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
    public function get(string $id = null): string
    {
        $participantId = Authorization::getAuthorizationProperty("participantId");
        $query = "SELECT * FROM voting WHERE id = :id AND participant_id = :participant_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":participant_id", $participantId);
        return parent::readGeneric($id, authorizedRoles: [Role::PARTICIPANT], statement: $statement);
    }

    /**
     * Read detailed data for a voting.
     * @param string|null $id The voting ID.
     * @return string The detailed data.
     */
    public function read(?string $id = null): string
    {
        return parent::readGeneric($id, authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT]);
    }

    /**
     * Get all task votings for the logged-in participant.
     * @param string|null $taskId The task's ID.
     * @return string The voting data in JSON format.
     * @OA\Get(
     *   path="/api/task/{taskId}/votings/",
     *   summary="Get all task votings for the logged-in participant.",
     *   tags={"Voting"},
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
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
    public function getAll(string $taskId = null): string
    {
        $participantId = Authorization::getAuthorizationProperty("participantId");
        $query = "SELECT * FROM voting WHERE task_id = :task_id AND participant_id = :participant_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":participant_id", $participantId);
        return parent::readAllGeneric(
            $taskId,
            authorizedRoles: [Role::PARTICIPANT],
            statement: $statement
        );
    }

    /**
     * Create a new voting for the task by the logged-in participant.
     * @param string|null $taskId The task's ID.
     * @param string|null $ideaId The idea's ID.
     * @param string|null $rating The rating.
     * @param string|null $detailRating The detailed rating.
     * @return string A success or failure statement in JSON format.
     * @OA\Post(
     *   path="/api/task/{taskId}/voting/",
     *   summary="Create a new voting for the task by the logged-in participant.",
     *   tags={"Voting"},
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"ideaId", "rating"},
     *         @OA\Property(property="ideaId", type="string"),
     *         @OA\Property(property="rating", type="integer", format="int"),
     *         @OA\Property(property="detailRating", type="number", format="float")
     *       )
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
    ): string {
        $participantId = Authorization::getAuthorizationProperty("participantId");
        $params = $this->formatParameters(
            [
                "task_id" => ["default" => $taskId, "url" => "task", "required" => true],
                "idea_id" => ["default" => $ideaId, "requestKey" => "ideaId", "required" => true],
                "participant_id" => ["default" => $participantId],
                "rating" => ["default" => $rating, "required" => true],
                "detail_rating" => ["default" => $detailRating, "requestKey" => "detailRating"]
            ]
        );

        return $this->addGeneric($params->task_id, $params, authorizedRoles: [Role::PARTICIPANT]);
    }

    /**
     * Update a vote for the task by the logged-in participant.
     * @param string|null $id The task's ID.
     * @param string|null $ideaId The idea's ID.
     * @param string|null $rating The rating.
     * @param string|null $detailRating The detailed rating.
     * @return string A success or failure statement in JSON format.
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
    ): string {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id],
                "idea_id" => ["default" => $ideaId, "requestKey" => "ideaId"],
                "rating" => ["default" => $rating],
                "detail_rating" => ["default" => $detailRating, "requestKey" => "detailRating"]
            ]
        );

        return $this->updateGeneric($params->id, $params, authorizedRoles: [Role::PARTICIPANT]);
    }

    /**
     * Delete a voting.
     * @param string|null $id The voting ID.
     * @return string A success or failure statement in JSON format.
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
    public function delete(?string $id = null): string
    {
        return parent::deleteGeneric($id, authorizedRoles: [Role::PARTICIPANT]);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        $query = "SELECT * FROM voting WHERE id = :id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":id", $id);

        if (Authorization::isParticipant()) {
            $participantId = Authorization::getAuthorizationProperty("participantId");
            $query = "SELECT * FROM voting WHERE id = :id AND participant_id = :participant_id";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":id", $id);
            $statement->bindParam(":participant_id", $participantId);
        }

        $statement->execute();
        $itemCount = $statement->rowCount();
        if ($itemCount > 0) {
            $result = $this->database->fetchFirst($statement);
            $taskId = $result["task_id"];
            return TaskController::getInstanceAuthorisationRole($taskId);
        }
        return null;
    }
}
