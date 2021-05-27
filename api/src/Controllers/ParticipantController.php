<?php
namespace PieLab\GAB\Controllers;

use PieLab\GAB\Models\AvatarSymbol;
use PieLab\GAB\Models\Participant;
use PieLab\GAB\Models\StateTask;
use PieLab\GAB\Models\Topic;
use PieLab\GAB\Models\Task;
use PieLab\GAB\Models\Role;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Config\Generator;

class ParticipantController extends Controller
{
    public function __construct()
    {
        parent::__construct("participant", Participant::class, SessionController::class, "session", "session_id");
    }

    /**
     * @OA\Post(
     *   path="/api/participant/connect/",
     *   summary="connect to a session",
     *   tags={"Participant"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"session_key", "ip_hash"},
     *         @OA\Property(property="session_key", type="string"),
     *         @OA\Property(property="ip_hash", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Participant")),
     *   @OA\Response(response="404", description="Not Found")
     * )
     * @throws Exception
     */
    public function connect(?string $session_key = null, ?string $ip_hash = null): string
    {
        $params = $this->formatParameters(
            [
                "session_key" => ["default" => $session_key],
                "ip_hash" => ["default" => $ip_hash, "type" => "MD5"]
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
                "participant_id" => $result->id,
                "browser_key" => $result->browser_key
            ]
        );
        http_response_code(200);
        return json_encode(
            new Participant((array)$result, $jwt)
        );
    }

    private function generateNewBrowserKey(string $session_key): string
    {
        $item_count = 1;
        $browser_key = "";
        while ($item_count > 0) {
            $browser_key = $session_key . "." . Generator::keygen(8, false);
            $query = "SELECT id FROM participant WHERE browser_key = :key";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(":key", $browser_key);
            $stmt->execute();
            $item_count = $stmt->rowCount();
        }
        return $browser_key;
    }

    public function read(?string $id = null): string
    {
        return parent::readGeneric(
            $id,
            authorizedRoles: [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT, Role::UNKNOWN]
        );
    }

    /**
     * Checks whether the user is authorised to edit the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function checkRights(?string $id): ?string
    {
        return $this->checkLogin($id);
    }

    protected function isRegistered(?string $session_id = null, ?string $ip_hash = null): ?object
    {
        $query = "SELECT * FROM participant
      WHERE session_id = :session_id AND ip_hash = :ip_hash";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":ip_hash", $ip_hash);
        $stmt->execute();
        $item_count = $stmt->rowCount();

        if ($item_count > 0) {
            return (object)$this->database->fetchFirst($stmt);
        }
        return null;
    }

    /**
     * @OA\Get(
     *   path="/api/participant/connect/{browser_key}/",
     *   summary="reconnect to a session",
     *   tags={"Participant"},
     *   @OA\Parameter(in="path", name="browser_key", description="the generated browser_key from the last connection to the session", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Participant")),
     *   @OA\Response(response="404", description="Not Found")
     * )
     * @throws Exception
     */
    public function reconnect(?string $browser_key = null): string
    {
        if (is_null($browser_key)) {
            $browser_key = $this->getUrlParameter("connect", -1);
        }
        $query = "SELECT * FROM participant WHERE browser_key = :browser_key";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":browser_key", $browser_key);
        $stmt->execute();
        $result = (object)$this->database->fetchFirst($stmt);
        $jwt = Authorization::generateToken(
            [
                "participant_id" => $result->id,
                "browser_key" => $result->browser_key
            ]
        );
        $result_obj = new Participant((array)$result, $jwt);
        http_response_code(200);
        return json_encode(
            $result_obj
        );
    }

    /**
     * @OA\Delete(
     *   path="/api/participant/",
     *   summary="Delete logged-in participant.",
     *   tags={"Participant"},
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(): string
    {
        $participant_id = Authorization::getAuthorizationProperty("participant_id");
        return parent::deleteGeneric($participant_id, authorizedRoles: [Role::PARTICIPANT]);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
        #TODO: What happens to the ideas and votes submitted by the user? Set id to zero or delete entry?
        $query = "UPDATE voting
          SET participant_id = null
          WHERE participant_id = :participant_id";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":participant_id", $id);
        $stmt->execute();

        $query = "UPDATE idea
          SET participant_id = null
          WHERE participant_id = :participant_id";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":participant_id", $id);
        $stmt->execute();

        $query = "DELETE FROM module_state WHERE participant_id = :participant_id";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":participant_id", $id);
        $stmt->execute();

        $query = "DELETE FROM random_idea WHERE participant_id = :participant_id";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":participant_id", $id);
        $stmt->execute();
    }

    /**
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
        $client_states = [strtoupper(StateTask::ACTIVE), strtoupper(StateTask::READ_ONLY)];
        $client_states = implode(',', $client_states);
        if (!Authorization::isParticipant()) {
            $login_id = getAuthorizationProperty("login_id");
            $query = "SELECT * FROM task
        WHERE FIND_IN_SET(state, :client_states)
        AND topic_id IN (
          SELECT topic.id
          FROM topic
          INNER JOIN session ON session.id = topic.session_id
          INNER JOIN session_role ON session_role.session_id = session.id
          WHERE session_role.login_id = :login_id
          AND session.expiration_date >= current_timestamp())";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(":login_id", $login_id);
            $stmt->bindParam(":client_states", $client_states);
        } else {
            $participant_id = Authorization::getAuthorizationProperty("participant_id");
            $query = "SELECT * FROM task
        WHERE FIND_IN_SET(state, :client_states)
        AND topic_id IN (
          SELECT topic.id
          FROM topic
          INNER JOIN session ON session.id = topic.session_id
          INNER JOIN participant ON participant.session_id = session.id
          WHERE participant.id = :participant_id and session.expiration_date >= current_timestamp())";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(":participant_id", $participant_id);
            $stmt->bindParam(":client_states", $client_states);
        }
        $stmt->execute();
        $result_data = $this->database->fetchAll($stmt);
        $result = [];
        foreach ($result_data as $result_item) {
            array_push($result, new Task($result_item));
        }
        http_response_code(200);
        return json_encode($result);
    }

    /**
     * @OA\Get(
     *   path="/api/topic/{topic_id}/participant_tasks/",
     *   summary="List of all topic tasks for the logged-in participant.",
     *   tags={"Participant"},
     *   @OA\Parameter(in="path", name="topic_id", description="ID of the topic", required=true),
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
    public function getTopicTasks(?string $topic_id = null): string
    {
        $client_states = [strtoupper(StateTask::ACTIVE), strtoupper(StateTask::READ_ONLY)];
        $client_states = implode(',', $client_states);
        $query = "SELECT * FROM task
      WHERE FIND_IN_SET(state, :client_states)
      AND topic_id = :topic_id";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":client_states", $client_states);

        return parent::readAllGenericStmt(
            $topic_id,
            [Role::MODERATOR, Role::FACILITATOR, Role::PARTICIPANT],
            $stmt,
            "topic",
            "topic_id",
            TopicController::class,
            Task::class
        );
    }

    /**
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
            $login_id = Authorization::getAuthorizationProperty("login_id");
            $query = "SELECT * FROM topic
        WHERE session_id IN (
          SELECT session.id
          FROM session
          INNER JOIN session_role ON session_role.session_id = session.id
          WHERE session_role.login_id = :login_id and session.expiration_date >= current_timestamp())";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(":login_id", $login_id);
        } else {
            $participant_id = Authorization::getAuthorizationProperty("participant_id");
            $query = "SELECT * FROM topic
        WHERE session_id IN (
          SELECT session.id
          FROM session
          INNER JOIN participant ON participant.session_id = session.id
          WHERE participant.id = :participant_id and session.expiration_date >= current_timestamp())";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(":participant_id", $participant_id);
        }
        $stmt->execute();
        $result_data = $this->database->fetchAll($stmt);
        $result = [];
        foreach ($result_data as $result_item) {
            array_push($result, new Topic($result_item));
        }
        http_response_code(200);
        return json_encode($result);
    }
}
