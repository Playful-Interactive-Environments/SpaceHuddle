<?php

namespace PieLab\GAB\Controllers;

use Exception;
use PieLab\GAB\Models\SessionRole;

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
        parent::__construct("session_role", SessionRole::class, LoginController::class, "login", "login_id");
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
    public function readAll(): string
    {
        #TODO: check rights for session
    }

    /**
     * Get the role of the username in the session.
     * @return string The user's role.
     * @OA\Get(
     *   path="/api/session/{sessionId}/authorized_users/{username}/",
     *   summary="Get the role of the username in the session.",
     *   tags={"Session Role"},
     *   @OA\Parameter(in="path", name="sessionId", description="ID of the session", required=true),
     *   @OA\Parameter(in="path", name="username", description="authorized user", required=true),
     *   @OA\Response(response="200", description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/SessionRole"),
     *   ),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function read(?string $id = null): string
    {
        #TODO: check rights for session
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
    public function add(): string
    {
        try {
            #TODO: check rights for session
        } catch (Exception $e) {
            http_response_code(404);
            $errorMessage = $e->getMessage();
            $this->connection->rollBack();
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => 'Error occurred:' . $errorMessage
                ]
            );
            die($error);
            #return $error;
        }
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
    public function update(?string $id = null): string
    {
        #TODO: check rights for session
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
    public function delete(?string $id = null): string
    {
        #TODO: check rights for session
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
    }
}
