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
        $loginId = Authorization::getAuthorizationProperty("login_id");
        $query = "SELECT * FROM session INNER JOIN session_role ON session_role.session_id = session.id
                  WHERE session_role.login_id = :login_id";
        /*$query = "SELECT * FROM session
          WHERE id IN (SELECT session_id FROM session_role WHERE login_id = :login_id)";*/
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":login_id", $loginId);
        $statement->execute();
        $resultData = $this->database->fetchAll($statement);
        $result = [];
        foreach ($resultData as $resultItem) {
            array_push($result, new Session($resultItem));
        }
        http_response_code(200);
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
        $loginId = Authorization::getAuthorizationProperty("login_id");
        if (is_null($id)) {
            $id = $this->getUrlParameter("session", -1);
        }
        $query = "SELECT * FROM session INNER JOIN session_role ON session_role.session_id = session.id
                  WHERE session.id = :id and session_role.login_id = :login_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":id", $id);
        $statement->bindParam(":login_id", $loginId);
        $statement->execute();
        $result = $this->database->fetchFirst($statement);
        http_response_code(200);
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
     * @param string|null $maxParticipants The maximum number of participants.
     * @param string|null $expirationDate The session's expiration date.
     * @return string The session data in JSON format.
     * @OA\Post(
     *   path="/api/session/",
     *   summary="Create a new session.",
     *   tags={"Session"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"title", "max_participants", "expiration_date"},
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="max_participants", type="integer", example=100),
     *         @OA\Property(property="expiration_date", type="string", format="date")
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
    public function add(?string $title = null, ?string $maxParticipants = null, ?string $expirationDate = null): string
    {
        $loginId = Authorization::getAuthorizationProperty("login_id"); # check if the user is logged in
        $connectionKey = $this->generateNewSessionKey();
        $params = $this->formatParameters(
            [
                "title" => ["default" => $title],
                "connection_key" => ["default" => $connectionKey],
                "max_participants" => ["default" => $maxParticipants],
                "expiration_date" => ["default" => $expirationDate]
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
        $loginId = Authorization::getAuthorizationProperty("login_id");
        $role = strtoupper(Role::MODERATOR);
        $query = "INSERT INTO session_role (session_id, login_id, role) VALUES (:session_id, :login_id, :role)";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $id);
        $statement->bindParam(":login_id", $loginId);
        $statement->bindParam(":role", $role);
        $statement->execute();
    }

    /**
     * Update a session.
     * @param string|null $id The session ID.
     * @param string|null $title The session title.
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
     *       mediaType="json",
     *       @OA\Schema(required={"title", "max_participants", "expiration_date"},
     *         @OA\Property(property="id", type="string", example="uuid"),
     *         @OA\Property(property="title", type="string"),
     *         @OA\Property(property="max_participants", type="integer", example=100),
     *         @OA\Property(property="expiration_date", type="string", format="date"),
     *         @OA\Property(property="public_screen_module_id", type="string", example=null)
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
        ?int $maxParticipants = null,
        ?string $expirationDate = null,
        ?string $publicScreenModuleId = null
    ): string {
        $params = $this->formatParameters(
            [
                "id" => ["default" => $id],
                "title" => ["default" => $title],
                "max_participants" => ["default" => $maxParticipants],
                "expiration_date" => ["default" => $expirationDate],
                "public_screen_module_id" => ["default" => $publicScreenModuleId]
            ]
        );

        return $this->updateGeneric($params->id, $params);
    }

    /**
     * Checks whether the user is authorised to edit the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function checkRights(?string $id): ?string
    {
        if (!Authorization::isParticipant()) {
            $loginId = Authorization::getAuthorizationProperty("login_id");
            if (is_null($id)) {
                return strtoupper(Role::MODERATOR);
            }
            $query = "SELECT * FROM session_role WHERE session_id = :session_id AND login_id = :login_id";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":session_id", $id);
            $statement->bindParam(":login_id", $loginId);
            $statement->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $result = $this->database->fetchFirst($statement);
                return strtoupper($result["role"]);
            }
        } else {
            $participantId = Authorization::getAuthorizationProperty("participant_id");
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
            // TODO: delete() has no parameters. Adapt the method or the code here?
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
}
