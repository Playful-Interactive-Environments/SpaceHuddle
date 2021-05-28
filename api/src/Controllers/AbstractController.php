<?php

namespace PieLab\GAB\Controllers;

use Exception;
use PDO;
use PDOStatement;
use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Config\Database;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\StateParticipant;
use Ramsey\Uuid\Uuid;

/**
 * Base controller class for REST-API calls.
 *
 * @OA\Info(title="GAB API", version="0.1")
 * @OA\Schemes(format="https")
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   in="header",
 *   name="Authorization",
 *   type="http",
 *   scheme="Bearer",
 *   bearerFormat="JWT"
 * )
 * @OA\Server(url="../../")
 */
abstract class AbstractController
{
    /**
     * The controller singleton instances. Holds all instances of various subtypes.
     * @var array
     */
    protected static array $instances;

    /**
     * The Database object.
     * @var Database
     */
    protected Database $database;

    /**
     * The PDO connection object.
     * @var PDO
     */
    protected PDO $connection;

    /**
     * The database table this controller works with.
     * @var string|null
     */
    protected ?string $table;

    /**
     * The model class this controller works with.
     * @var string|null
     */
    protected ?string $class;

    /**
     * The parent controller class.
     * @var string|null
     */
    protected ?string $parentController;

    /**
     * The parent database table.
     * @var string|null
     */
    protected ?string $parentTable;

    /**
     * The parent ID.
     * @var string|null
     */
    protected ?string $parentIdName;

    /**
     * URL parameters.
     * @var string|null
     */
    protected ?string $urlParameter;

    /**
     * Creates a new controller. Protected for singleton instantiation.
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
        $this->database = Database::getInstance();
        $this->connection = $this->database->getConnection();
        $this->class = $class;
        $this->table = $table;
        $this->parentController = $parentController;
        $this->parentTable = $parentTable;
        $this->parentIdName = $parentIdName;
        if (is_null($urlParameter)) {
            $urlParameter = $table;
        }
        $this->urlParameter = $urlParameter;
        http_response_code(400);
    }

    /**
     * Checks if all generic parameters have been set.
     * @return bool Returns true if all generic parameters have been set.
     */
    protected function allGenericParameterSet(): bool
    {
        return (
            isset($this->table) and
            isset($this->urlParameter) and
            isset($this->class) and
            isset($this->parentController) and
            isset($this->parentTable) and
            isset($this->parentIdName)
        );
    }

    /**
     * Checks if the basic generic parameters have been set.
     * @return bool Returns true if the basic generic parameters have been set.
     */
    protected function genericTableParameterSet(): bool
    {
        return (
            isset($this->table) and
            isset($this->urlParameter) and
            isset($this->class)
        );
    }

    /**
     * Creates a single instance of a controller. Uses late static binding to work with controller subclasses.
     * @return static Returns a static instance of a controller type.
     */
    public static function getInstance(): static
    {
        if (!isset(static::$instances[static::class])) {
            static::$instances[static::class] = new static();
        }
        return static::$instances[static::class];
    }

    /**
     * Get a list of all authorised entries in the specified statement.
     * @param string|null $rightId Foreign key linked to the table that restricts the result set.
     * @param array $authorizedRoles List of authorised roles.
     * @param PDOStatement|null $statement Statement to be executed.
     * @param string|null $rightTable Database table name to which the database table is linked.
     * @param string|null $rightIdName Name of the foreign key to which the database table is linked.
     * @param string|null $rightsController Controller class of the foreign key relationship.
     * @param string|null $resultClass Result class of the foreign key relationship.
     * @return string Returns a json encoded list of all authorised entries in the specified database table.
     */
    public function readAllGenericStmt(
        ?string $rightId = null,
        array $authorizedRoles = [Role::MODERATOR, Role::FACILITATOR],
        ?PDOStatement $statement = null,
        ?string $rightTable = null,
        ?string $rightIdName = null,
        ?string $rightsController = null,
        ?string $resultClass = null
    ): string {
        if ($this->allGenericParameterSet()) {
            if (is_null($rightsController)) {
                $rightsController = $this->parentController;
            }
            if (is_null($rightIdName)) {
                $rightIdName = $this->parentIdName;
            }
            if (is_null($rightTable)) {
                $rightTable = $this->parentTable;
            }
        }

        if ($this->genericTableParameterSet()) {
            if (is_null($resultClass)) {
                $resultClass = $this->class;
            }
        }

        if (isset($rightTable) and is_null($rightId)) {
            $rightId = $this->getUrlParameter($rightTable);
        }

        if (isset($rightId) and isset($rightsController) and isset($rightIdName) and isset($resultClass)) {
            if (is_null($rightId)) {
                $rightId = $this->getUrlParameter($rightTable);
            }
            $role = $rightsController::checkInstanceReadRights($rightId);
            if ($this->isAuthorized($role, $authorizedRoles)) {
                $statement->bindParam(":$rightIdName", $rightId);
                $statement->execute();
                $resultData = $this->database->fetchAll($statement);
                $result = [];
                foreach ($resultData as $resultItem) {
                    array_push($result, new $resultClass($resultItem));
                }
                http_response_code(200);
                return json_encode($result);
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "User is not authorized to read $rightTable."
                    ]
                );
                die($error);
            }
        } else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "generic parameters not set"
                ]
            );
            die($error);
        }
    }

    /**
     * Get a list of all authorised entries in the specified database table.
     * @param string|null $parentId Foreign key linked to the table that restricts the result set.
     * @param array $authorizedRoles List of authorised roles.
     * @param PDOStatement|null $statement Statement to be executed.
     * @param string|null $parentTable Database table name to which the database table is linked.
     * @param string|null $parentIdName Name of the foreign key to which the database table is linked.
     * @param string|null $parentController Controller class of the foreign key relationship.
     * @return string Returns a json encoded list of all authorised entries in the specified database table.
     */
    public function readAllGeneric(
        ?string $parentId = null,
        array $authorizedRoles = [Role::MODERATOR, Role::FACILITATOR],
        ?PDOStatement $statement = null,
        ?string $parentTable = null,
        ?string $parentIdName = null,
        ?string $parentController = null
    ): string {
        if ($this->allGenericParameterSet()) {
            if (is_null($parentTable)) {
                $parentTable = $this->parentTable;
            }
            if (is_null($parentIdName)) {
                $parentIdName = $this->parentIdName;
            }
            if (is_null($parentController)) {
                $parentController = $this->parentController;
            }
        }

        if (
            $this->genericTableParameterSet()
            and isset($parentTable)
            and isset($parentIdName)
            and isset($parentController)
        ) {
            if (is_null($parentId)) {
                $parentId = $this->getUrlParameter($parentTable);
            }
            $role = $parentController::checkInstanceReadRights($parentId);
            if ($this->isAuthorized($role, $authorizedRoles)) {
                if (is_null($statement)) {
                    $query = "SELECT * FROM $this->table WHERE $parentIdName = :$parentIdName";
                    $statement = $this->connection->prepare($query);
                }
                $statement->bindParam(":$parentIdName", $parentId);
                $statement->execute();
                $resultData = $this->database->fetchAll($statement);
                $result = [];
                foreach ($resultData as $resultItem) {
                    array_push($result, new $this->class($resultItem));
                }
                http_response_code(200);
                return json_encode($result);
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "User is not authorized to read $this->table."
                    ]
                );
                die($error);
            }
        } else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "Generic parameters not set."
                ]
            );
            die($error);
        }
    }

    /**
     * Get a specific entry in the specified database table.
     * @param string|null $id Primary key to be queried.
     * @param array $authorizedRoles List of authorised roles.
     * @param PDOStatement|null $statement Statement to be executed.
     * @param string|null $role User role.
     * @return string Returns a json encoded specific entry in the specified database table.
     */
    public function readGeneric(
        ?string $id = null,
        array $authorizedRoles = [Role::MODERATOR, Role::FACILITATOR],
        ?PDOStatement $statement = null,
        ?string $role = null
    ): string {
        if ($this->genericTableParameterSet()) {
            if (is_null($id)) {
                $id = $this->getUrlParameter($this->urlParameter);
            }
            if (is_null($role)) {
                $role = $this->checkReadRights($id);
            }
            if ($this->isAuthorized($role, $authorizedRoles)) {
                if (is_null($statement)) {
                    $query = "SELECT * FROM $this->table WHERE id = :id";
                    $statement = $this->connection->prepare($query);
                }
                $statement->bindParam(":id", $id);
                $statement->execute();
                $result = $this->database->fetchFirst($statement);
                http_response_code(200);
                return json_encode(new $this->class($result));
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "User is not authorized to read $this->table."
                    ]
                );
                die($error);
            }
        } else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "generic parameters not set"
                ]
            );
            die($error);
        }
    }

    /**
     * Get a specific entry in the specified database table.
     * @param string|null $id Primary key to be queried.
     * @return string Returns a json encoded specific entry in the specified database table.
     */
    public function read(?string $id = null): string
    {
        return $this->readGeneric($id);
    }

    /**
     * Insert an entry into the database table.
     * @param string|null $parentId Foreign key linked to the table.
     * @param array|object $parameter Data to be inserted.
     * @param array $authorizedRoles List of authorised roles.
     * @param bool $insertId Table contains a uuid as primary key which should be generated automatically.
     * @param string|null $duplicateCheck SQL statement which checks whether the data to be inserted is unique (if this
     * is necessary).
     * @param array|object|null $parameterDependencies Dependent data to be included.
     * @return string Inserted data in the json format.
     */
    public function addGeneric(
        ?string $parentId,
        array|object $parameter,
        array $authorizedRoles = [Role::MODERATOR],
        bool $insertId = true,
        ?string $duplicateCheck = "",
        array|object|null $parameterDependencies = null
    ): string {
        if (isset($parentId)) {
            if ($this->allGenericParameterSet()) {
                $role = $this->parentController::checkInstanceRights($parentId);
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "generic parameters not set"
                    ]
                );
                die($error);
            }
        } else {
            $role = $this->getAuthorisationRole(null);
        }
        if (!$this->isAuthorized($role, $authorizedRoles)) {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "User is not authorized to add this $this->table."
                ]
            );
            die($error);
        }

        try {
            $this->connection->beginTransaction();
            if (!is_array($parameter)) {
                $parameter = [$parameter];
            }
            if (!is_array($parameterDependencies)) {
                $parameterDependencies = [$parameterDependencies];
            }

            foreach ($parameter as $paramIndex => $paramItem) {
                $columns = null;
                $values = null;
                $bindParameter = [];

                if ($insertId) {
                    $id = self::uuid();

                    $columns = "id";
                    $values = ":id";
                    $bindParameter[":id"] = $id;
                }
                foreach ($paramItem as $key => $value) {
                    if (isset($columns)) {
                        $columns = "$columns, `$key`";
                        $values = "$values, :$key";
                    } else {
                        $columns = "`$key`";
                        $values = ":$key";
                    }
                    $bindParameter[":$key"] = $value;
                }

                $query = "INSERT INTO $this->table ($columns) SELECT $values $duplicateCheck";
                $statement = $this->connection->prepare($query);
                $statement->execute($bindParameter);
                $itemCount = $statement->rowCount();

                if ($itemCount > 0) {
                    if ($insertId) {
                        $this->addDependencies($id, $parameterDependencies[$paramIndex]);
                    }
                }
            }

            $this->connection->commit();

            if ($itemCount > 0) {
                http_response_code(200);
                if ($insertId) {
                    return $this->read($id);
                } else {
                    return json_encode(
                        [
                            "state" => "Success",
                            "message" => "$this->table was successfully added."
                        ]
                    );
                }
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "no data was added"
                    ]
                );
                die($error);
            }
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
        }
    }

    /**
     * Include dependent data.
     * @param string $id Primary key of the linked table entry
     * @param array|object|null $parameter Dependent data to be included.
     */
    protected function addDependencies(string $id, array|object|null $parameter)
    {
    }

    /**
     * Update an entry into the database table.
     * @param string $id Primary key to be updated.
     * @param array|object $parameter Data to be updated.
     * @param array $authorizedRoles List of authorised roles.
     * @return string Updated data in the json format.
     */
    public function updateGeneric(
        string $id,
        array|object $parameter,
        array $authorizedRoles = [Role::MODERATOR]
    ): string {
        if ($this->genericTableParameterSet()) {
            $role = $this->getAuthorisationRole($id);
            if (!$this->isAuthorized($role, $authorizedRoles)) {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "User is not authorized to update this $this->table."
                    ]
                );
                die($error);
            }

            try {
                $this->connection->beginTransaction();

                $queryData = "";
                $bindParameter = [":id" => $id];
                foreach ($parameter as $key => $value) {
                    if ($key != "id") {
                        $queryData .= "`$key` = NVL(:$key, `$key`), ";
                        $bindParameter[":$key"] = $value;
                    }
                }
                $queryData = substr_replace($queryData, " ", -2);

                $query = "UPDATE $this->table SET $queryData WHERE id = :id";
                $statement = $this->connection->prepare($query);
                $statement->execute($bindParameter);
                $itemCount = $statement->rowCount();
                $this->connection->commit();

                if ($itemCount > 0) {
                    http_response_code(200);
                    return $this->read($id);
                } else {
                    http_response_code(404);
                    $error = json_encode(
                        [
                            "state" => "Failed",
                            "message" => "No data was found to modify."
                        ]
                    );
                    die($error);
                }
            } catch (Exception $e) {
                http_response_code(404);
                $errorMessage = $e->getMessage();
                $this->connection->rollBack();
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "An error occurred: " . $errorMessage
                    ]
                );
                die($error);
                #return $error;
            }
        } else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "Generic parameters not set."
                ]
            );
            die($error);
        }
    }

    /**
     * Delete dependent data.
     * @param string $id Primary key of the linked table entry.
     */
    protected function deleteDependencies(string $id)
    {
    }

    /**
     * Delete an entry into the database table.
     * @param string|null $id Primary key to be deleted.
     * @param array $authorizedRoles List of authorised roles.
     * @param PDOStatement|null $statement Statement to be executed.
     * @return string Success status of the statement.
     */
    public function deleteGeneric(
        ?string $id = null,
        array $authorizedRoles = [Role::MODERATOR],
        ?PDOStatement $statement = null
    ): string {
        if ($this->genericTableParameterSet()) {
            if (is_null($id)) {
                $id = $this->getUrlParameter($this->urlParameter);
            }
            $role = $this->getAuthorisationRole($id);
            if (!$this->isAuthorized($role, $authorizedRoles)) {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "User is not authorized to delete this $this->table."
                    ]
                );
                die($error);
                #return $error;
            }

            $handleTransaction = !$this->connection->inTransaction();
            try {
                if ($handleTransaction) {
                    $this->connection->beginTransaction();
                }

                $this->deleteDependencies($id);

                if (is_null($statement)) {
                    $query = "DELETE FROM $this->table WHERE id = :id";
                    $statement = $this->connection->prepare($query);
                    $statement->bindParam(":id", $id);
                }
                $statement->execute();
                $itemCount = $statement->rowCount();

                if ($handleTransaction) {
                    $this->connection->commit();
                }
            } catch (Exception $e) {
                http_response_code(404);
                $errorMessage = $e->getMessage();
                $this->connection->rollBack();
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "Error occurred: " . $errorMessage
                    ]
                );
                die($error);
                #return $error;
            }

            if ($itemCount > 0) {
                http_response_code(200);
                return json_encode(
                    [
                        "state" => "Success",
                        "message" => "$this->table was successfully deleted."
                    ]
                );
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "$this->table not found."
                    ]
                );
                die($error);
            }
        } else {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => "generic parameters not set"
                ]
            );
            die($error);
        }
    }

    /**
     * Check if the user role is part of the authorised roles.
     * @param string|null $role User role
     * @param array $authorizedRoles List of authorised roles.
     * @return bool Authorisation state
     */
    public function isAuthorized(?string $role, array $authorizedRoles = [Role::MODERATOR]): bool
    {
        if (isset($role)) {
            $role = strtoupper($role);
            $authorizedRoles = array_map("strtoupper", $authorizedRoles);
            return in_array($role, $authorizedRoles);
        }
        return false;
    }

    /**
     * Checks the access role via which the logged-in user may access the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function getAuthorisationRole(?string $id): ?string
    {
        if (is_null($id)) {
            $loginId = Authorization::getAuthorizationProperty("login_id");
            return strtoupper(Role::MODERATOR);
        }

        if ($this->allGenericParameterSet()) {
            $query = "SELECT * FROM $this->table WHERE id = :id";
            $statement = $this->connection->prepare($query);
            $statement->bindParam(":id", $id);
            $statement->execute();
            $itemCount = $statement->rowCount();
            if ($itemCount > 0) {
                $result = $this->database->fetchFirst($statement);
                $parentId = $result[$this->parentIdName];
                return $this->parentController::checkInstanceRights($parentId);
            }
        }
        return null;
    }

    /**
     * Check the access role via which one's own user data can be edited.
     * @param string|null $id Primary key to be checked.
     * @return string Role with which the user is authorised to access the entry.
     */
    public function getLoginRole(?string $id): string
    {
        if (Authorization::isLoggedIn()) {
            if (Authorization::isUser()) {
                $loginId = Authorization::getAuthorizationProperty("login_id");
                if (is_null($id) or $id == $loginId) {
                    return Role::MODERATOR;
                }
            }
            if (Authorization::isParticipant()) {
                $participantId = Authorization::getAuthorizationProperty("participant_id");
                if (is_null($id) or $id == $participantId) {
                    $state = StateParticipant::ACTIVE;
                    $query = "SELECT * FROM participant WHERE id = :participant_id AND state like :state";
                    $statement = $this->connection->prepare($query);
                    $statement->bindParam(":participant_id", $participantId);
                    $statement->bindParam(":state", $state);
                    $statement->execute();
                    $itemCount = $statement->rowCount();
                    if ($itemCount > 0) {
                        return Role::PARTICIPANT;
                    }
                    return Role::PARTICIPANT_INACTIVE;
                }
            }
        }

        return Role::UNKNOWN;
    }

    /**
     * Checks whether the user is authorised to read the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function checkReadRights(?string $id): ?string
    {
        return $this->getAuthorisationRole($id);
    }

    /**
     * Check the edit rights of the static singleton instance.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public static function checkInstanceRights(?string $id): ?string
    {
        $instance = self::getInstance();
        return $instance->getAuthorisationRole($id);
    }

    /**
     * Check the read rights of the static singleton instance.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public static function checkInstanceReadRights(?string $id): ?string
    {
        $instance = self::getInstance();
        return $instance->checkReadRights($id);
    }

    /**
     * Creates a random uuid.
     * @return string Returns a random uuid.
     */
    public static function uuid(): string
    {
        $uuid = Uuid::uuid4();
        return $uuid->toString();
    }

    /**
     * Extrudes a parameter from the REST url.
     * @param string $parameterName Name of the parameter. The name is separated by a slash before the parameter value
     * ($parameter_name/$parameter_value).
     * @param mixed|null $defaultValue Default value if no parameter entry ist found.
     * @return mixed Returns the parameter value.
     */
    public static function getUrlParameter(string $parameterName, mixed $defaultValue = null): mixed
    {
        $parameterValue = $defaultValue;
        if (count($_GET) > 0 and array_key_exists($parameterName, $_GET)) {
            $parameterValue = $_GET[$parameterName];
        } else {
            $urlParts = explode("/", $_SERVER["REQUEST_URI"]);
            $urlPartCount = count($urlParts);
            for ($index = $urlPartCount - 1; $index > 0; $index--) {
                $testItem = $urlParts[$index];
                if (strlen($testItem) > 0) {
                    $parameterDescription = $urlParts[$index - 1];
                    if ($parameterDescription == $parameterName) {
                        $parameterValue = $testItem;
                        break;
                    }
                }
            }
        }
        return $parameterValue;
    }

    /**
     * Get a list with all url parameters.
     * @param int $firstParamIndex Start index where to start the search for url parameters.
     * @return object Returns an object with all url parameters.
     */
    public static function getUrlParameters(int $firstParamIndex = 1): object
    {
        $params = [];
        foreach ($_GET as $key => $value) {
            $params[$key] = $value;
        }

        $urlParts = explode("/", $_SERVER["REQUEST_URI"]);
        $urlPartCount = count($urlParts);
        $apiIndex = 0;
        for ($index = 0; $index < $urlPartCount; $index++) {
            $testItem = $urlParts[$index];
            if ($testItem == "api") {
                $apiIndex = $index;
                break;
            }
        }

        for ($index = $apiIndex + $firstParamIndex; $index < $urlPartCount; $index += 2) {
            $testItem = $urlParts[$index];
            if (strlen($testItem) > 0 and $index + 1 < $urlPartCount) {
                $params[$testItem] = $urlParts[$index + 1];
            }
        }

        return (object)$params;
    }

    /**
     * Break down url hierarchy.
     * @param int $firstParamIndex Start index where to start the search for url parameters.
     * @return array Returns a list of the url hierarchy.
     */
    public static function getUrlHierarchy(int $firstParamIndex = 1): array
    {
        $hierarchy = [];

        $urlParts = explode("/", $_SERVER["REQUEST_URI"]);
        $urlPartCount = count($urlParts);
        $apiIndex = 0;
        for ($index = 0; $index < $urlPartCount; $index++) {
            $testItem = $urlParts[$index];
            if ($testItem == "api") {
                $apiIndex = $index;
                break;
            }
        }
        $apiIndex += 1;
        $firstParamIndex -= 1;

        for ($index = $apiIndex; $index < $apiIndex + $firstParamIndex; $index++) {
            $testItem = $urlParts[$index];
            if (strlen($testItem) > 0) {
                array_push($hierarchy, $testItem);
            }
        }

        for ($index = $apiIndex + $firstParamIndex; $index < $urlPartCount; $index += 2) {
            $testItem = $urlParts[$index];
            if (strlen($testItem) > 0) {
                array_push($hierarchy, $testItem);
            }
        }

        return $hierarchy;
    }

    /**
     * Extrudes a parameter from the response body.
     * @param string|null $parameter_name Name of the parameter.
     * @param mixed|null $default_value Default value if no parameter entry ist found.
     * @return mixed Returns the parameter value.
     */
    public static function getBodyParameter(?string $parameter_name, mixed $default_value = null): mixed
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "data body incorrectly formatted",
                    "message" => "Data body cannot be read. JSON is not valid."
                ]
            );
            die($error);
        }
        if (array_key_exists($parameter_name, $data)) {
            return $data[$parameter_name];
        }
        if (is_null($parameter_name)) {
            return $data;
        }
        return $default_value;
    }

    /**
     * Get a list with all body parameters.
     * @return object Returns an object with all body parameters.
     */
    public static function getAllBodyParameter(): object
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(404);
            $error = json_encode(
                [
                    "state" => "data body incorrectly formatted",
                    "message" => "Data body cannot be read. JSON is not valid."
                ]
            );
            die($error);
        }
        return $data;
    }

    /**
     * Prepare the list of body parameters in the specified format.
     * @param array $parameter List of the name and type of the body parameters.
     * @return object Returns the formatted data.
     */
    public static function formatParameters(array $parameter): object
    {
        $paramData = [];
        foreach ($parameter as $key => $keyDefinition) {
            $keyType = null;
            $keyResult = null;
            $value = null;
            if (isset($keyDefinition)) {
                $keyDefinition = (object)$keyDefinition;
                if (isset($keyDefinition->type)) {
                    $keyType = $keyDefinition->type;
                }
                if (isset($keyDefinition->result)) {
                    $keyResult = $keyDefinition->result;
                }
                if (isset($keyDefinition->default)) {
                    $value = $keyDefinition->default;
                }
                if (is_null($value) and isset($keyDefinition->url)) {
                    $value = self::getUrlParameter($keyDefinition->url);
                }
            }

            if (isset($keyResult) and $keyResult == "all") {
                $value = self::getBodyParameter(null);
            }

            if (is_null($value)) {
                $value = self::getBodyParameter($key);
            }

            if (isset($value) and isset($keyType)) {
                if ($keyType == "JSON") {
                    $value = json_encode((object)$value);
                } elseif ($keyType == "ARRAY") {
                    $value = $value;
                } elseif ($keyType == "MD5") {
                    $value = md5($value);
                } else {
                    $value = strtoupper($value);
                    if (!defined("$keyType::$value")) {
                        http_response_code(404);
                        $error = json_encode(
                            [
                                "state" => "wrong $keyType",
                                "message" => "the specified $keyType does not exist"
                            ]
                        );
                        die($error);
                    }
                }
            }
            $paramData[$key] = $value;
        }

        return (object)$paramData;
    }

    /**
     * Checks if this is the REST call you are looking for.
     * @param string $searchMethod Request type.
     * @param int $firstParamIndex Start index where to start the search for url parameters.
     * @param string $searchDetailHierarchy Break down url hierarchy.
     * @return bool Returns if this is the REST call you are looking for.
     */
    public static function isRestCall(
        string $searchMethod,
        int $firstParamIndex = 1,
        string $searchDetailHierarchy = ""
    ): bool {
        $urlHierarchy = self::getUrlHierarchy($firstParamIndex);

        $searchHierarchyParts = [];
        for ($i = 0; $i < 1; $i++) {
            array_push($searchHierarchyParts, $urlHierarchy[$i]);
        }

        $searchHierarchyParts = array_merge($searchHierarchyParts, explode("/", $searchDetailHierarchy));

        for ($index = 0; $index < count($searchHierarchyParts); $index++) {
            if (strlen($searchHierarchyParts[$index]) == 0) {
                unset($searchHierarchyParts[$index]);
            }
        }

        if (count($searchHierarchyParts) == count($urlHierarchy)) {
            if ($searchHierarchyParts == $urlHierarchy) {
                if ($_SERVER["REQUEST_METHOD"] == $searchMethod) {
                    return true;
                }
            }
        }
        return false;
    }
}
