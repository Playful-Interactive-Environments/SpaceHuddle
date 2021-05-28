<?php

namespace PieLab\GAB\Controllers;

use Exception;
use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\Session;
use PieLab\GAB\Models\SessionRole;
use PieLab\GAB\Models\VotingResult;

/**
 * Controller for roles in a session.
 * @package PieLab\GAB\Controllers
 */
class SessionRoleController extends AbstractController
{
    /**
     * Create a new SessionRoleController.
     */
    protected function __construct()
    {
        parent::__construct("session_role", SessionRole::class, SessionController::class, "session", "session_id");
    }

    /**
     * List all authorized users for the session.
     * @return string A list of all authorized users in JSON format.
     * @OA\Get(
     *   path="/api/session/{sessionId}/authorized_users/",
     *   summary="List of all authorized users for the session.",
     *   tags={"Session Role"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(type="array", @OA\Items(ref="#/components/schemas/SessionRole")),
     *     )
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function readAll(?string $sessionId = null): string
    {
        $query = "SELECT * FROM login INNER JOIN session_role ON session_role.login_id = login.id
                  WHERE session_role.session_id = :session_id";
        $statement = $this->connection->prepare($query);

        return parent::readAllGeneric(
            $sessionId,
            [Role::MODERATOR],
            $statement
        );
    }

    /**
     * Get the role of the username in the session.
     * @return string The user's role.
     * @OA\Get(
     *   path="/api/session/{sessionId}/own_user_role/",
     *   summary="Get the role of the username in the session.",
     *   tags={"Session Role"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $sessionId = null): string
    {
        $loginId = Authorization::getAuthorizationProperty("loginId");
        $query = "SELECT * FROM login INNER JOIN session_role ON session_role.login_id = login.id
                  WHERE session_role.session_id = :session_id and session_role.login_id = :login_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":login_id", $loginId);

        $result = json_decode(parent::readAllGeneric(
            $sessionId,
            [Role::MODERATOR, Role::FACILITATOR],
            $statement
        ));

        if (count($result) > 0) {
            return json_encode($result[0]);
        }
    }

    /**
     * Add a new authorized user to the session.
     * @return string The updated session role data.
     * @OA\Post(
     *   path="/api/session/{sessionId}/authorized_users/",
     *   summary="Add a new authorized user to the session.",
     *   tags={"Session Role"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"username", "role"},
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="role", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function add(?string $sessionId = null, ?string $username = null, ?string $role = null): string
    {
        $params = $this->formatParameters(
            [
                "session_id" => ["default" => $sessionId, "url" => "session", "required" => true],
                "username" => ["default" => $username, "required" => true],
                "role" => ["default" => $role, "type" => Role::class, "required" => true]
            ]
        );

        $query = "SELECT * FROM login WHERE username = :username ";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":username", $params->username);
        $statement->execute();
        $itemCount = $statement->rowCount();
        if ($itemCount > 0) {
            $result = (object)$this->database->fetchFirst($statement);
            $params->login_id = $result->id;
            unset($params->username);
        }
        else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "Username not exists."
                ]
            );
            die($error);
        }

        return $this->addGeneric(null, $params, authorizedRoles: [Role::MODERATOR], insertId: false);
    }

    /**
     * Update the role of a authorized user for a session.
     * @param string|null $id The session's ID.
     * @return string The updated session role data.
     * @OA\Put(
     *   path="/api/session/{sessionId}/authorized_users/",
     *   summary="Update the role of a authorized user for a session.",
     *   tags={"Session Role"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="json",
     *       @OA\Schema(required={"id"},
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="role", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(?string $sessionId = null, ?string $username = null, ?string $role = null): string
    {
        $params = $this->formatParameters(
            [
                "session_id" => ["default" => $sessionId, "url" => "session", "required" => true],
                "username" => ["default" => $username, "required" => true],
                "role" => ["default" => $role, "type" => Role::class, "required" => true]
            ]
        );

        $query = "UPDATE session_role 
            SET role = :role 
            WHERE session_id = :session_id 
            AND login_id IN (SELECT id FROM login WHERE username = :username)";

        $this->updateGeneric($params->session_id, $params, authorizedRoles: [Role::MODERATOR], query: $query);

        return json_encode(
            [
                "state" => "Success",
                "message" => "Role was successfully updated."
            ]
        );
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        return SessionController::getInstanceAuthorisationRole($id);
    }

    /**
     * Remove username from a session.
     * @param string|null $id The session's ID.
     * @return string The updated session role data.
     * @OA\Delete(
     *   path="/api/session/{sessionId}/authorized_users/{username}/",
     *   summary="Remove username for a session.",
     *   tags={"Session Role"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\Parameter(in="path", name="username", description="Username of the user who should be deprived of the
     *   session permission", required=true),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(?string $sessionId = null, ?string $username = null): string
    {
        $params = $this->formatParameters(
            [
                "session_id" => ["default" => $sessionId, "url" => "session", "required" => true],
                "username" => ["default" => $username, "url" => "authorized_users", "required" => true]
            ]
        );

        $query = "DELETE FROM session_role 
            WHERE session_id = :session_id 
            AND login_id IN (SELECT id FROM login WHERE username = :username)";;
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":session_id", $params->session_id);
        $stmt->bindParam(":username", $params->username);

        return parent::deleteGeneric(
            $params->session_id,
            authorizedRoles: [Role::MODERATOR],
            statement: $stmt
        );
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
    }
}
