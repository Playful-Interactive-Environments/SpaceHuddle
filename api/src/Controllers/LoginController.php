<?php

namespace PieLab\GAB\Controllers;

use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\User;

/**
 * Controller for user registration and login.
 * @package PieLab\GAB\Controllers
 */
class LoginController extends AbstractController
{
    /**
     * Creates a new LoginController.
     */
    public function __construct()
    {
        parent::__construct("login", User::class);
    }

    /**
     * Perform a login with an existing user.
     * @param string|null $username The username.
     * @param string|null $password The password.
     * @return string The response to the login attempt in JSON format.
     * @OA\Post(
     *   path="/api/user/login/",
     *   summary="Perform a login with an existing user",
     *   tags={"User"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"username", "password"},
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="password", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="accessToken", type="string")
     *       )
     *     )),
     *   @OA\Response(response="404", description="Not Found")
     * )
     */
    public function login(?string $username = null, ?string $password = null): string
    {
        $params = $this->formatParameters(
            [
                "username" => ["default" => $username, "required" => true],
                "password" => ["default" => $password, "type" => "MD5", "required" => true]
            ]
        );

        $query = "SELECT * FROM login WHERE username = :username AND password = :password";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":username", $params->username);
        $stmt->bindParam(":password", $params->password);
        $stmt->execute();
        $item_count = $stmt->rowCount();
        if ($item_count == 0) {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Login Failed",
                    "message" => "Username or password wrong"
                ]
            );
            die($error);
        }
        $result = (object)$this->database->fetchFirst($stmt);
        $jwt = Authorization::generateToken(
            [
                "loginId" => $result->id,
                "username" => $result->username
            ]
        );

        http_response_code(200);
        return json_encode(
            [
                "message" => "Successful login.",
                "accessToken" => $jwt
            ]
        );
    }

    /**
     * Register a new user.
     * @param string|null $username The username.
     * @param string|null $password The password.
     * @param string|null $passwordConfirmation The password confirmation.
     * @return string A JSON encoded reply on success or failure.
     * @OA\Post(
     *   path="/api/user/register/",
     *   summary="Register a new user",
     *   tags={"User"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"username", "password", "passwordConfirmation"},
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="passwordConfirmation", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found")
     * )
     */
    public function register(
        ?string $username = null,
        ?string $password = null,
        ?string $passwordConfirmation = null
    ): string {
        $params = $this->formatParameters(
            [
                "username" => ["default" => $username, "required" => true],
                "password" => ["default" => $password, "type" => "MD5", "required" => true],
                "passwordConfirmation" => ["default" => $passwordConfirmation, "type" => "MD5", "required" => true]
            ]
        );
        if ($this->checkUser($params->username)) {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "User already exists."
                ]
            );
            die($error);
        }
        $this->checkPasswordConfirmation($params->password, $params->passwordConfirmation);
        if (isset($params->passwordConfirmation)) {
            unset($params->passwordConfirmation);
        }

        $this->addGeneric(null, $params, authorizedRoles: [Role::UNKNOWN]);

        http_response_code(200);
        return json_encode(
            [
                "state" => "Success",
                "message" => "User was created."
            ]
        );
    }

    /**
     * Checks if the provided password and confirmation match.
     * @param string $password The password.
     * @param string $passwordConfirmation The password confirmation.
     */
    private function checkPasswordConfirmation(string $password, string $passwordConfirmation): void
    {
        if ($password !== $passwordConfirmation) {
            http_response_code(401);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "Password and confirmation do not match."
                ]
            );
            die($error);
        }
    }

    /**
     * Checks if a user already exists.
     * @param string $username The username.
     * @return bool Returns true if a user exists, otherwise false.
     */
    private function checkUser(string $username): bool
    {
        $query = "SELECT * FROM login WHERE username = :username";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":username", $username);
        $statement->execute();
        $itemCount = $statement->rowCount();

        return ($itemCount > 0);
    }

    /**
     * Check if a provided password is correct.
     * @param string $id The user ID.
     * @param string $password The password.
     * @return bool Returns true if a password matches.
     */
    private function checkPassword(string $id, string $password): bool
    {
        $query = "SELECT * FROM login WHERE id = :id and password = :password";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":password", $password);
        $stmt->execute();
        $item_count = $stmt->rowCount();

        return ($item_count > 0);
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        return $this->getLoginRole($id);
    }

    /**
     * Read data for a specific user.
     * @param string|null $id The user ID.
     * @return string Returns a JSON encoded entry for the user.
     */
    public function read(?string $id = null): string
    {
        return parent::readGeneric($id, role: Role::MODERATOR);
    }

    /**
     * Change the password for the currently logged in user.
     * @param string|null $oldPassword The old password.
     * @param string|null $password The new password.
     * @param string|null $passwordConfirmation The confirmation for the new password.
     * @return string A JSON encoded message about the result of the query.
     * @OA\Put(
     *   path="/api/user/",
     *   summary="Change the password of the logged-in user.",
     *   tags={"User"},
     *   @OA\RequestBody(
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(required={"oldPassword", "password", "passwordConfirmation"},
     *         @OA\Property(property="oldPassword", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="passwordConfirmation", type="string")
     *       )
     *     )
     *   ),
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function update(
        ?string $oldPassword = null,
        ?string $password = null,
        ?string $passwordConfirmation = null
    ): string {
        $login_id = Authorization::getAuthorizationProperty("loginId");
        $params = $this->formatParameters(
            [
                "id" => ["default" => $login_id],
                "oldPassword" => ["default" => $oldPassword, "type" => "MD5", "required" => true],
                "password" => ["default" => $password, "type" => "MD5", "required" => true],
                "passwordConfirmation" => ["default" => $passwordConfirmation, "type" => "MD5", "required" => true]
            ]
        );
        $this->checkPasswordConfirmation($params->password, $params->passwordConfirmation);
        if (!$this->checkPassword($login_id, $params->oldPassword)) {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "The old password is wrong."
                ]
            );
            die($error);
        }
        if (isset($params->oldPassword)) {
            unset($params->oldPassword);
        }
        if (isset($params->passwordConfirmation)) {
            unset($params->passwordConfirmation);
        }

        $this->updateGeneric($params->id, $params);
        return json_encode(
            [
                "state" => "Success",
                "message" => "The password was successfully updated."
            ]
        );
    }

    /**
     * Deletes the currently logged-in user.
     * @return string A JSON encoded status message.
     * @OA\Delete(
     *   path="/api/user/",
     *   summary="Delete the logged-in user.",
     *   tags={"User"},
     *   @OA\Response(response="200", description="Success"),
     *   @OA\Response(response="404", description="Not Found"),
     *   security={{"api_key": {}}, {"bearerAuth": {}}}
     * )
     */
    public function delete(): string
    {
        $login_id = Authorization::getAuthorizationProperty("loginId");
        return parent::deleteGeneric($login_id);
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
        $role = strtoupper(Role::MODERATOR);
        $query = "SELECT * FROM session_role WHERE login_id = :login_id AND role like :role";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":login_id", $id);
        $statement->bindParam(":role", $role);
        $statement->execute();

        $resultData = $this->database->fetchAll($statement);
        foreach ($resultData as $result_item) {
            $sessionId = $result_item["session_id"];

            $query = "SELECT * FROM session_role WHERE session_id = :session_id AND role like :role";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":session_id", $sessionId);
            $statement->bindParam(":role", $role);
            $statement->execute();
            $itemCount = $statement->rowCount();

            if ($itemCount === 1) {
                $session = SessionController::getInstance();
                $session->delete($sessionId);
            }
        }

        $query = "DELETE FROM session_role WHERE login_id = :login_id";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(":login_id", $id);
        $statement->execute();
    }
}
