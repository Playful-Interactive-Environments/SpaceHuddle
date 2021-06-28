<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Config\Generator;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\Session;

/**
 * Controller for sessions.
 * @package PieLab\GAB\Controllers
 */
class SessionController extends AbstractController
{
    /**
     * Creates a new SessionController.
     */
    protected function __construct()
    {
        parent::__construct("session", Session::class);
    }

    /**
     * List all the sessions for which the user is authorized.
     * @return string A list of sessions in JSON format.
     * @OA\Get(
     *   path="/api/sessions/",
     *   summary="List of all sessions for which the user is authorized.",
     *   tags={"Session"},
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Session")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   @OA\Response(response="401", description="Unauthorized"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAll(): string
    {
        if (Authorization::isUser()) {
          $userId = Authorization::getAuthorizationProperty("userId");
          $userType = 'USER';
        } else {
          $userId = Authorization::getAuthorizationProperty("participantId");
          $userType = 'PARTICIPANT';
        }
        $query = "SELECT * FROM session INNER JOIN session_permission ON session_permission.session_id = session.id
                  WHERE session_permission.user_id = :user_id AND session_permission.user_type = :user_type";
        /*$query = "SELECT * FROM session
          WHERE id IN (SELECT session_id FROM session_role WHERE user_id = :user_id)";*/
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":user_id", $userId);
        $statement->bindParam(":user_type", $userType);
        $statement->execute();
        $resultData = $this->database->fetchAll($statement);
        $result = [];
        foreach ($resultData as $resultItem) {
            array_push($result, new Session($resultItem));
        }
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        return json_encode($result);
    }

    /**
     * Read detail data for the session with the specified id.
     * @param string|null $id The session ID.
     * @return string Detailed session data in JSON format.
     * @OA\Get(
     *   path="/api/session/{id}/",
     *   summary="Detail data for the session with the specified id.",
     *   tags={"Session"},
     *   @OA\Parameter(in="path", name="id", description="ID of session to return", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Session"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $id = null): string
    {
        if (Authorization::isUser()) {
          $userId = Authorization::getAuthorizationProperty("userId");
          $userType = 'USER';
        } else {
          $userId = Authorization::getAuthorizationProperty("participantId");
          $userType = 'PARTICIPANT';
        }
        if (is_null($id)) {
            $id = $this->getUrlParameter("session", -1);
        }
        $query = "SELECT * FROM session INNER JOIN session_permission ON session_permission.session_id = session.id
                  WHERE session.id = :id AND session_permission.user_id = :user_id AND session_permission.user_type = :user_type";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":id", $id);
        $statement->bindParam(":user_id", $userId);
        $statement->bindParam(":user_type", $userType);
        $statement->execute();
        $result = $this->database->fetchFirst($statement);
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        return json_encode(new Session($result));
    }

    /**
     * Read data from a session specified by a session key.
     * @param string $sessionKey
     * @return object The session data.
     */
    public function readByKey(string $sessionKey): object
    {
        $query = "SELECT * FROM session WHERE connection_key = :session_key";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_key", $sessionKey);
        $statement->execute();
        return (object)$this->database->fetchFirst($statement, "Wrong session key.");
    }

    /**
     * Generate a new session key.
     * @return string The session key.
     */
    private function generateNewSessionKey(): string
    {
        $itemCount = 1;
        $connectionKey = "";
        while ($itemCount > 0) {
            $connectionKey = Generator::keygen(8, false);
            $query = "SELECT id FROM session WHERE connection_key = :key";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":key", $connectionKey);
            $statement->execute();
            $itemCount = $statement->rowCount();
        }
        return $connectionKey;
    }

    /**
     * Create a new session.
     * @param string|null $title The session title.
     * @param string|null $description The session description.
     * @param string|null $maxParticipants The maximum number of participants.
     * @param string|null $expirationDate The session's expiration date.
     * @return string The session data in JSON format.
     * @OA\Post(
     *   path="/api/session/",
     *   summary="Create a new session.",
     *   tags={"Session"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"title", "maxParticipants", "expirationDate"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="maxParticipants", type="integer", example=100),
     *         @OA\Property(property="expirationDate", type="string", format="date")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Session"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function add(?string $title = null, ?string $description = null, ?string $maxParticipants = null, ?string $expirationDate = null): string
    {
        $userId = Authorization::getAuthorizationProperty("userId"); # check if the user is logged in
        $connectionKey = $this->generateNewSessionKey();
        $params = $this->formatParameters(
            [
                "title" => ["default" => $title, "required" => true],
                "description" => ["default" => $description],
                "connection_key" => ["default" => $connectionKey, "requestKey" => "connectionKey"],
                "max_participants" => ["default" => $maxParticipants, "requestKey" => "maxParticipants", "required" => true],
                "expiration_date" => ["default" => $expirationDate, "requestKey" => "expirationDate", "required" => true]
            ]
        );

        return $this->addGeneric(null, $params);
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     */
    protected function addDependencies(string $id, array|object|null $parameter)
    {
        $userId = Authorization::getAuthorizationProperty("userId");
        $role = strtoupper(Role::MODERATOR);
        $query = "INSERT INTO session_role (session_id, user_id, role) VALUES (:session_id, :user_id, :role)";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $id);
        $statement->bindParam(":user_id", $userId);
        $statement->bindParam(":role", $role);
        $statement->execute();
    }

    /**
     * Update a session.
     * @param string|null $id The session ID.
     * @param string|null $title The session title.
     * @param string|null $description The session description.
     * @param int|null $maxParticipants The maximum number of participants.
     * @param string|null $expirationDate The session's expiration daten.
     * @param string|null $publicScreenModuleId The public screen module's ID.
     * @return string The updated session data in JSON format.
     * @OA\Put(
     *   path="/api/session/",
     *   summary="Update a session.",
     *   tags={"Session"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"id"},
     *         @OA\Property(property="id", type="string", example="uuid"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="description", type="string"),
     *         @OA\Property(property="maxParticipants", type="integer", example=100),
     *         @OA\Property(property="expirationDate", type="string", format="date"),
     *         @OA\Property(property="publicScreenModuleId", type="string", example=null)
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Session"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(
        ?string $id = null,
        ?string $title = null,
        ?string $description = null,
        ?int $maxParticipants = null,
        ?string $expirationDate = null,
        ?string $publicScreenModuleId = null
    ): string {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id, "required" => true],
                "title" => ["default" => $title],
                "description" => ["default" => $description],
                "max_participants" => ["default" => $maxParticipants, "requestKey" => "maxParticipants"],
                "expiration_date" => ["default" => $expirationDate, "requestKey" => "expirationDate"],
                "public_screen_module_id" => ["default" => $publicScreenModuleId, "requestKey" => "publicScreenModuleId"]
            ]
        );

        return $this->updateGeneric($params->id, $params);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        if (!Authorization::isParticipant()) {
            $userId = Authorization::getAuthorizationProperty("userId");
            if (is_null($id)) {
                return strtoupper(Role::MODERATOR);
            }
            $query = "SELECT * FROM session_role WHERE session_id = :session_id AND user_id = :user_id";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":session_id", $id);
            $statement->bindParam(":user_id", $userId);
            $statement->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $result = $this->database->fetchFirst($statement);
                return strtoupper($result["role"]);
            }
        } else {
            $participantId = Authorization::getAuthorizationProperty("participantId");
            $query = "SELECT * FROM participant WHERE session_id = :session_id AND id = :participant_id";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":session_id", $id);
            $statement->bindParam(":participant_id", $participantId);
            $statement->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $result = $this->database->fetchFirst($statement);
                return strtoupper(Role::PARTICIPANT);
            }
        }
        return null;
    }

    /**
     * Delete a session.
     * @param string|null $id The session's ID.
     * @return string A success or failure message in JSON format.
     * @OA\Delete(
     *   path="/api/session/{id}/",
     *   summary="Delete a session.",
     *   tags={"Session"},
     *   @OA\Parameter(in="path", name="id", description="ID of session to delete", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $id = null): string
    {
        return parent::deleteGeneric($id);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
        $query = "SELECT * FROM participant WHERE session_id = :session_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $id);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        $participant = ParticipantController::getInstance();
        foreach ($resultData as $resultItem) {
            $participantId = $resultItem["id"];
            $participant->delete($participantId);
        }

        $query = "SELECT * FROM topic WHERE session_id = :session_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $id);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        $topic = TopicController::getInstance();
        foreach ($resultData as $resultItem) {
            $topicId = $resultItem["id"];
            $topic->delete($topicId);
        }

        $query = "DELETE FROM resource WHERE session_id = :session_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $id);
        $statement->execute();

        $query = "DELETE FROM session_role WHERE session_id = :session_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $id);
        $statement->execute();
    }

    /**
     * Set a task to be displayed on the public screen for the session.
     * @param string|null $sessionId The session ID.
     * @param string|null $taskId The task ID.
     * @return string Returns the success message in JSON format.
     * @OA\Put(
     *   path="/api/session/{sessionId}/public_screen/{taskId}/",
     *   summary="Set a task to be displayed on the public screen for the session.",
     *   tags={"Public Screen"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session to be updated", required=true),
     *   @OA\Parameter(in="path", name="taskId", description="ID of the task to be displayed on the public screen", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function setPublicScreen(?string $sessionId = null, string $taskId = null): string
    {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $sessionId, "url" => "session", "required" => true],
                "task_id" => ["default" => $taskId, "url" => "public_screen", "required" => true]
            ]
        );

        $query = "SELECT * FROM module WHERE task_id = :task_id ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":task_id", $params->task_id);
        $statement->execute();
        $itemCount = $statement->rowCount();
        if ($itemCount > 0) {
            $result = (object)$this->database->fetchFirst($statement);
            $params->public_screen_module_id = $result->id;
            unset($params->task_id);
        }
        else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "Task has no module and can not be set for public screen."
                ]
            );
            die($error);
        }

        $this->updateGeneric($params->id, $params);

        return json_encode(
            [
                "state" => "Success",
                "message" => "Public screen was successfully updated."
            ]
        );
    }
}
