<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Config\Generator;
use PieLab\GAB\Models\AvatarSymbol;
use PieLab\GAB\Models\Participant;
use PieLab\GAB\Models\ParticipantTask;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\StateParticipant;
use PieLab\GAB\Models\StateTask;
use PieLab\GAB\Models\Task;
use PieLab\GAB\Models\Topic;

/**
 * Controller for participant interaction.
 * @package PieLab\GAB\Controllers
 */
class ParticipantController extends AbstractController
{
    /**
     * Creates a new ParticipantController.
     */
    protected function __construct()
    {
        parent::__construct("participant", Participant::class, SessionController::class, "session", "session_id");
    }

    /**
     * Connect to a session as a participant.
     * @param string|null $sessionKey The session's key.
     * @param string|null $ip The participant's IP hash.
     * @return string The participant data in JSON format.
     * @OA\Post(
     *   path="/api/participant/connect/",
     *   summary="Connect to a session",
     *   tags={"Participant"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"sessionKey", "ip"},
     *         @OA\Property(property="sessionKey", type="string"),
     *         @OA\Property(property="ip", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Participant")),
     *   @OA\Response(response="404", description="Not Found")
     * )
     */
    public function connect(?string $sessionKey = null, ?string $ip = null): string
    {
        if (is_null($ip) and is_null($this->getBodyParameter("ip"))) {
          $ip = $this->getClientIp();
        }

        $params = $this->formatParameters(
            [
                "session_key" => ["default" => $sessionKey, "requestKey" => "sessionKey", "required" => true],
                "ip_hash" => ["default" => $ip, "type" => "MD5", "requestKey" => "ip", "required" => true],
                "state" => ["default" => StateParticipant::ACTIVE, "type" => StateParticipant::class]
            ]
        );
        $params->session_id = SessionController::getInstance()->readByKey($params->session_key)->id;

        $result = $this->isRegistered($params->session_id, $params->ip_hash);
        if (is_null($result)) {
            $params->browser_key = $this->generateNewBrowserKey($params->session_key);
            $params->color = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
            $params->symbol = AvatarSymbol::getRandomValue();
            unset($params->session_key);

            $result = (object)json_decode($this->addGeneric(null, $params, authorizedRoles: array(Role::UNKNOWN)));
        }

        $jwt = Authorization::generateToken(
            [
                "participantId" => $result->id,
                "browserKey" => $result->browserKey
            ]
        );
        $result->accessToken = $jwt;
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: POST, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        return json_encode($result);
    }

    /**
     * Determine client IP address
     * @return string Client IP
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    /**
     * Generates a new browser key.
     * @param string $session_key The session's key.
     * @return string Returns the browser key.
     */
    private function generateNewBrowserKey(string $session_key): string
    {
        $itemCount = 1;
        $browserKey = "";
        while ($itemCount > 0) {
            $browserKey = $session_key . "." . Generator::keygen(8, false);
            $query = "SELECT id FROM participant WHERE browser_key = :key";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":key", $browserKey);
            $statement->execute();
            $itemCount = $statement->rowCount();
        }
        return $browserKey;
    }

    /**
     * Reads data about the participant.
     * @param string|null $id The participant ID.
     * @return string The data in JSON format.
     */
    public function read(?string $id = null): string
    {
        return parent::readGeneric(
            $id,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT, Role::UNKNOWN]
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        if (Authorization::isLoggedIn()) {
            if (Authorization::isParticipant()) {
                return $this->getLoginRole($id);
            } else {
                $query = "SELECT * FROM participant WHERE id = :id";
                $statement = $this->connection->prepare($query);
                $statement->bindParam(":id", $id);
                $statement->execute();
                $itemCount = $statement->rowCount();
                if ($itemCount > 0) {
                    $participant = (object)$this->database->fetchFirst($statement);
                    return SessionController::getInstanceAuthorisationRole($participant->session_id);
                }
            }
        }

        return Role::UNKNOWN;
    }

    /**
     * Checks if a participant is registered.
     * @param string|null $sessionId The session's ID.
     * @param string|null $ipHash The participant's IP hash.
     * @return object|null Returns an object if registered, otherwise null.
     */
    protected function isRegistered(?string $sessionId = null, ?string $ipHash = null): ?object
    {
        $query = "SELECT * FROM participant WHERE session_id = :session_id AND ip_hash = :ip_hash";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":session_id", $sessionId);
        $statement->bindParam(":ip_hash", $ipHash);
        $statement->execute();
        $itemCount = $statement->rowCount();

        if ($itemCount > 0) {
            return new Participant($this->database->fetchFirst($statement));
        }
        return null;
    }

    /**
     * Reconnects a participant to a session.
     * @param string|null $browserKey The browser key.
     * @return string The participant data in JSON format.
     * @OA\Get(
     *   path="/api/participant/connect/{browserKey}/",
     *   summary="Reconnect to a session",
     *   tags={"Participant"},
     *   @OA\Parameter(in="path", name="browserKey", description="the generated browser_key from the last connection to
     *   the session", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Participant")),
     *   @OA\Response(response="404", description="Not Found")
     * )
     */
    public function reconnect(?string $browserKey = null): string
    {
        if (is_null($browserKey)) {
            $browserKey = $this->getUrlParameter("connect", -1);
        }
        $query = "SELECT * FROM participant WHERE browser_key = :browser_key";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":browser_key", $browserKey);
        $statement->execute();
        $result = (object)$this->database->fetchFirst($statement);
        $jwt = Authorization::generateToken(
            [
                "participantId" => $result->id,
                "browserKey" => $result->browser_key
            ]
        );
        $resultObject = new Participant((array)$result, $jwt);
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        return json_encode($resultObject);
    }

    /**
     * Set the participant state.
     * @param StateParticipant|null $state The state of the participant.
     * @return string The updated data in JSON format.
     * @OA\Put(
     *   path="/api/participant/state/{state}/",
     *   summary="Set the participant state.",
     *   tags={"Participant"},
     *   @OA\Parameter(in="path", name="state",
     *     description="display status of the participant",
     *     required=true,
     *     @OA\Schema(ref="#/components/schemas/StateParticipant")),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function setState(StateParticipant|null $state = null): string
    {
        $participantId = Authorization::getAuthorizationProperty("participantId");
        $params = $this->formatParameters(
            [
                "id" => ["default" => $participantId],
                "state" => ["default" => $state, "type" => StateParticipant::class, "url" => "state", "required" => true]
            ]
        );

        return $this->updateGeneric(
            $params->id,
            $params,
            authorizedRoles: [Role::PARTICIPANT, Role::PARTICIPANT_INACTIVE, Role::MODERATOR]
        );
    }

    /**
     * Delete the logged-in participant.
     * @return string Success or failure message in JSON format.
     * @OA\Delete(
     *   path="/api/participant/",
     *   summary="Delete logged-in participant.",
     *   tags={"Participant"},
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $participantId = null): string
    {
        if (is_null($participantId)) {
            $participantId = Authorization::getAuthorizationProperty("participantId");
        }
        return parent::deleteGeneric($participantId, authorizedRoles: [Role::PARTICIPANT, Role::MODERATOR]);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id): void
    {
        $query = "UPDATE voting
          SET participant_id = null
          WHERE participant_id = :participant_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":participant_id", $id);
        $statement->execute();

        $query = "UPDATE idea
          SET participant_id = null
          WHERE participant_id = :participant_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":participant_id", $id);
        $statement->execute();

        $query = "DELETE FROM module_state WHERE participant_id = :participant_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":participant_id", $id);
        $statement->execute();

        $query = "DELETE FROM random_idea WHERE participant_id = :participant_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":participant_id", $id);
        $statement->execute();
    }

    /**
     * List all tasks for the currently logged-in participant.
     * @return string The list of tasks in JSON format.
     * @OA\Get(
     *   path="/api/participant/tasks/",
     *   summary="List of all tasks for the logged-in participant.",
     *   tags={"Participant"},
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/ParticipantTask")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function getTasks(): string
    {
        $clientStates = [strtoupper(StateTask::ACTIVE), strtoupper(StateTask::READ_ONLY)];
        $clientStates = implode(",", $clientStates);
        if (!Authorization::isParticipant()) {
            $userId = Authorization::getAuthorizationProperty("userId");
            $query = "SELECT * FROM topic
              INNER JOIN task ON topic.id = task.topic_id
              WHERE FIND_IN_SET(task.state, :client_states)
              AND task.topic_id IN (
                SELECT topic.id
                FROM topic
                INNER JOIN session ON session.id = topic.session_id
                INNER JOIN session_role ON session_role.session_id = session.id
                WHERE session_role.user_id = :user_id
                AND session.expiration_date >= current_timestamp())";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":user_id", $userId);
        } else {
            $participantId = Authorization::getAuthorizationProperty("participantId");
            $query = "SELECT * FROM topic
              INNER JOIN task ON topic.id = task.topic_id
              WHERE FIND_IN_SET(task.state, :client_states)
              AND task.topic_id IN (
                SELECT topic.id
                FROM topic
                INNER JOIN session ON session.id = topic.session_id
                INNER JOIN participant ON participant.session_id = session.id
                WHERE participant.id = :participant_id and session.expiration_date >= current_timestamp())";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":participant_id", $participantId);
        }
        $statement->bindParam(":client_states", $clientStates);
        $statement->execute();
        $resultData = $this->database->fetchAll($statement);
        $result = [];
        foreach ($resultData as $resultItem) {
            array_push($result, new ParticipantTask($resultItem));
        }
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        return json_encode($result);
    }

    /**
     * List all topic tasks for the logged-in participant.
     * @param string|null $topicId The topic's ID.
     * @return string A list of all topic tasks in JSON format.
     * @OA\Get(
     *   path="/api/topic/{topicId}/participant_tasks/",
     *   summary="List of all topic tasks for the logged-in participant.",
     *   tags={"Participant"},
     *   @OA\Parameter(in="path", name="topicId", description="ID of the topic", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Task")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function getTopicTasks(?string $topicId = null): string
    {
        $clientStates = [strtoupper(StateTask::ACTIVE), strtoupper(StateTask::READ_ONLY)];
        $clientStates = implode(',', $clientStates);
        $query = "SELECT * FROM task
      WHERE FIND_IN_SET(state, :client_states)
      AND topic_id = :topic_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":client_states", $clientStates);

        return parent::readAllGenericStmt(
            $topicId,
            [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            $statement,
            "topic",
            "topic_id",
            TopicController::class,
            Task::class
        );
    }

    /**
     * List all topics for the current participant.
     * @return string A list of topics in JSON format.
     * @OA\Get(
     *   path="/api/participant/topics/",
     *   summary="List of all topics for the logged-in participant.",
     *   tags={"Participant"},
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/Topic")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function getTopics(): string
    {
        if (!Authorization::isParticipant()) {
            $userId = Authorization::getAuthorizationProperty("userId");
            $query = "SELECT * FROM topic
        WHERE session_id IN (
          SELECT session.id
          FROM session
          INNER JOIN session_role ON session_role.session_id = session.id
          WHERE session_role.user_id = :user_id and session.expiration_date >= current_timestamp())";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":user_id", $userId);
        } else {
            $participantId = Authorization::getAuthorizationProperty("participantId");
            $query = "SELECT * FROM topic
        WHERE session_id IN (
          SELECT session.id
          FROM session
          INNER JOIN participant ON participant.session_id = session.id
          WHERE participant.id = :participant_id and session.expiration_date >= current_timestamp())";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":participant_id", $participantId);
        }
        $statement->execute();
        $resultData = $this->database->fetchAll($statement);
        $result = [];
        foreach ($resultData as $result_item) {
            array_push($result, new Topic($result_item));
        }
        http_response_code(200);
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, OPTIONS");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Credentials: true");
        return json_encode($result);
    }
}
