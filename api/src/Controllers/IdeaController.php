<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Idea;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\StateIdea;
use PieLab\GAB\Models\TaskType;

/**
 * Controller class for ideas.
 * @package PieLab\GAB\Controllers
 */
class IdeaController extends AbstractController
{
    /**
     * The type of task involved.
     * @var string
     */
    protected string $taskType = TaskType::BRAINSTORMING;

    /**
     * Creates a new IdeaController.
     * @param string|null $table The table this controller will work on.
     * @param string|null $class The model class this controller will work with.
     * @param string|null $parentController The parent controller class.
     * @param string|null $parentTable The parent table.
     * @param string|null $parentIdName The parent ID.
     * @param string|null $urlParameter URL parameters.
     */
    protected function __construct(
        ?string $table = null,
        ?string $class = null,
        ?string $parentController = null,
        ?string $parentTable = null,
        ?string $parentIdName = null,
        ?string $urlParameter = null
    ) {
        if (is_null($table)) {
            $table = "idea";
        }
        if (is_null($class)) {
            $class = Idea::class;
        }
        if (is_null($parentController)) {
            $parentController = TaskController::class;
        }
        if (is_null($parentTable)) {
            $parentTable = "task";
        }
        if (is_null($parentIdName)) {
            $parentIdName = "task_id";
        }
        parent::__construct($table, $class, $parentController, $parentTable, $parentIdName, $urlParameter);
        $this->taskType = TaskType::BRAINSTORMING;
    }

    /**
     * List all ideas for the task.
     * @param string|null $taskId The task's ID.
     * @param bool $treatParticipantsSeparately Treat participants separately.
     * @return string Returns a json encoded list of all ideas for the task.
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
    public function readAllFromTask(?string $taskId = null, bool $treatParticipantsSeparately = true): string
    {
        $taskType = strtoupper($this->taskType);
        if (!Authorization::isParticipant() or !$treatParticipantsSeparately) {
            $query = "SELECT * FROM idea WHERE task_id = :task_id AND task_id IN (SELECT id FROM task
                     WHERE task_type like :task_type)";
            $statement = $this->connection->prepare($query);
        } else {
            $participantId = Authorization::getAuthorizationProperty("participant_id");
            $query = "SELECT * FROM idea WHERE task_id = :task_id AND participant_id = :participant_id
                     AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":participant_id", $participantId);
        }
        $statement->bindParam(":task_type", $taskType);
        return parent::readAllGeneric(
            $taskId,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            statement: $statement
        );
    }

    /**
     * List all ideas for a topic.
     * @param string|null $topicId The topic's idea.
     * @param bool $treatParticipantsSeparately Treat participants separately.
     * @return string Returns a json encoded list of all ideas for the topic.
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
    public function readAllFromTopic(?string $topicId = null, bool $treatParticipantsSeparately = true): string
    {
        $taskType = strtoupper($this->taskType);
        if (!Authorization::isParticipant() or !$treatParticipantsSeparately) {
            $query = "SELECT * FROM idea WHERE task_id IN (SELECT id FROM task WHERE topic_id = :topic_id
                                                           AND task_type like :task_type)";
            $statement = $this->connection->prepare($query);
        } else {
            $participantId = Authorization::getAuthorizationProperty("participant_id");
            $query = "SELECT * FROM idea WHERE participant_id = :participant_id AND
                         task_id IN (SELECT id FROM task WHERE topic_id = :topic_id AND task_type like :task_type)";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":participant_id", $participantId);
        }
        $statement->bindParam(":task_type", $taskType);
        return parent::readAllGeneric(
            $topicId,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            statement: $statement,
            parentTable: "topic",
            parentIdName: "topic_id",
            parentController: TopicController::class
        );
    }

    /**
     * Read data for the idea with the specified ID.
     * @param string|null $id The idea's ID.
     * @param bool $treatParticipantsSeparately Treat participants separately.
     * @return string Returns json encoded data about this idea.
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
    public function read(?string $id = null, bool $treatParticipantsSeparately = true): string
    {
        $taskType = strtoupper($this->taskType);
        if (!Authorization::isParticipant() or !$treatParticipantsSeparately) {
            $query = "SELECT * FROM idea WHERE id = :id AND task_id
                                          IN (SELECT id FROM task WHERE task_type like :task_type)";
            $statement = $this->connection->prepare($query);
        } else {
            $participant_id = Authorization::getAuthorizationProperty("participant_id");
            $query = "SELECT * FROM idea WHERE id = :id AND participant_id = :participant_id
                     AND task_id IN (SELECT id FROM task WHERE task_type like :task_type)";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":participant_id", $participant_id);
        }
        $statement->bindParam(":task_type", $taskType);
        return parent::readGeneric(
            $id,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            statement: $statement
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        $query = "SELECT * FROM idea WHERE id = :id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":id", $id);

        if (Authorization::isParticipant()) {
            $participantId = Authorization::getAuthorizationProperty("participant_id");
            $query = "SELECT * FROM idea WHERE id = :id AND participant_id = :participant_id";
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

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationReadRole(?string $id): ?string
    {
        return parent::getAuthorisationRole($id);
    }

    /**
     * Creates a new idea for the task.
     * @param string|null $taskId The task's ID.
     * @param string|null $keywords The idea's keywords.
     * @param string|null $description The idea's description.
     * @param string|null $link The idea's link.
     * @param string|null $image An image for the idea.
     * @return string Returns the added idea in JSON format.
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
        ?string $taskId = null,
        ?string $keywords = null,
        ?string $description = null,
        ?string $link = null,
        ?string $image = null
    ): string {
        $participant_id = Authorization::getAuthorizationProperty("participant_id");
        $state = strtoupper(StateIdea::NEW);
        $params = $this->formatParameters(
            [
                "task_id" => ["default" => $taskId, "url" => "task"],
                "keywords" => ["default" => $keywords],
                "description" => ["default" => $description],
                "link" => ["default" => $link],
                "image" => ["default" => $image],
                "state" => ["default" => $state],
                "participant_id" => ["default" => $participant_id]
            ]
        );

        return $this->addGeneric($params->task_id, $params, authorizedRoles: [Role::PARTICIPANT]);
    }

    /**
     * Creates a new idea for the topic.
     * @param string|null $topicId The topic's ID.
     * @param string|null $keywords The idea's keywords.
     * @param string|null $description The idea's description.
     * @param string|null $link The idea's link.
     * @param string|null $image An image for the idea.
     * @return string Returns the added idea in JSON format.
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
        ?string $topicId = null,
        ?string $keywords = null,
        ?string $description = null,
        ?string $link = null,
        ?string $image = null
    ): string {
        if (is_null($topicId)) {
            $topicId = $this->getUrlParameter("topic");
        }
        $taskType = strtoupper($this->taskType);

        $query = "SELECT * FROM task WHERE topic_id = :topic_id AND task_type like :task_type";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":topic_id", $topicId);
        $statement->bindParam(":task_type", $taskType);
        $statement->execute();
        $itemCount = $statement->rowCount();
        if ($itemCount > 0) {
            $result = $this->database->fetchFirst($statement);
            $taskId = $result["id"];
            return $this->addToTask($taskId, $keywords, $description, $link, $image);
        } else {
            http_response_code(404);
            $error = json_encode(
                [
                  "state" => "Failed",
                  "message" => "Topic has no BRAINSTORMING task."
                ]
            );
            die($error);
        }
    }

    /**
     * Update an idea.
     * @param string|null $id The idea's ID.
     * @param string|null $state The idea's state.
     * @param string|null $keywords The idea's keywords.
     * @param string|null $description The idea's description.
     * @param string|null $link The idea's link.
     * @param string|null $image An image for the idea.
     * @return string Updated data in JSON format.
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
    ): string {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id],
                "keywords" => ["default" => $keywords],
                "description" => ["default" => $description],
                "link" => ["default" => $link],
                "image" => ["default" => $image],
                "state" => ["default" => $state, "type" => StateIdea::class]
            ]
        );

        return $this->updateGeneric(
            $params->id,
            $params,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT]
        );
    }

    /**
     * Delete an idea.
     * @param string|null $id The idea's ID.
     * @return string Success status of the statement.
     * @OA\Delete(
     *   path="/api/idea/{id}/",
     *   summary="Delete an idea.",
     *   tags={"Idea"},
     *   @OA\Parameter(in="path", name="id", description="ID of idea to delete", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $id = null): string
    {
        return parent::deleteGeneric($id, authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT]);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id): void
    {
        $query = "DELETE FROM voting WHERE idea_id = :idea_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":idea_id", $id);
        $statement->execute();

        $query = "DELETE FROM hierarchy WHERE group_idea_id = :idea_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":idea_id", $id);
        $statement->execute();

        $query = "DELETE FROM hierarchy WHERE sub_idea_id = :idea_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":idea_id", $id);
        $statement->execute();

        $query = "DELETE FROM selection_group_idea WHERE idea_id = :idea_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":idea_id", $id);
        $statement->execute();

        $query = "DELETE FROM random_idea WHERE idea_id = :idea_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":idea_id", $id);
        $statement->execute();
    }
}
