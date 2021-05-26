<?php

namespace PieLab\GAB\Controllers;

use Exception;
use PDO;
use PDOStatement;
use PieLab\GAB\Config\Authorization;
use PieLab\GAB\Config\Database;
use PieLab\GAB\Models\Role;
use PieLab\GAB\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Base class for REST-API calls.
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
class Controller
{
    protected static array $instances;
    protected Database $database;
    protected PDO $connection;
    protected ?string $table;
    protected ?string $class;
    protected ?string $parent_controller;
    protected ?string $parent_table;
    protected ?string $parent_id_name;
    protected ?string $url_parameter;

    public function __construct(
        ?string $table = null,
        ?string $class = null,
        ?string $parent_controller = null,
        ?string $parent_table = null,
        ?string $parent_id_name = null,
        ?string $url_parameter = null
    ) {
        $this->database = Database::getInstance();
        $this->connection = $this->database->getConnection();
        $this->class = $class;
        $this->table = $table;
        $this->parent_controller = $parent_controller;
        $this->parent_table = $parent_table;
        $this->parent_id_name = $parent_id_name;
        if (is_null($url_parameter)) {
            $url_parameter = $table;
        }
        $this->url_parameter = $url_parameter;
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
            isset($this->url_parameter) and
            isset($this->class) and
            isset($this->parent_controller) and
            isset($this->parent_table) and
            isset($this->parent_id_name)
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
            isset($this->url_parameter) and
            isset($this->class)
        );
    }

    /**
     * singleton pattern
     * @return Controller Returns a static instance of a controller type.
     */
    public static function getInstance(): Controller
    {
        $class = get_called_class();

        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class;
        }
        return self::$instances[$class];
    }

    /**
     * Get a list of all authorised entries in the specified statement.
     * @param string|null $right_id Foreign key linked to the table that restricts the result set.
     * @param array $authorized_roles List of authorised roles.
     * @param PDOStatement|null $stmt Statement to be executed.
     * @param string|null $right_table Database table name to which the database table is linked.
     * @param string|null $right_id_name Name of the foreign key to which the database table is linked.
     * @param string|null $rights_controller Controller class of the foreign key relationship.
     * @param string|null $result_class Result class of the foreign key relationship.
     * @return string Returns a json encoded list of all authorised entries in the specified database table.
     */
    public function readAllGenericStmt(
        ?string $right_id = null,
        array $authorized_roles = [Role::MODERATOR, Role::FACILITATOR],
        ?PDOStatement $stmt = null,
        ?string $right_table = null,
        ?string $right_id_name = null,
        ?string $rights_controller = null,
        ?string $result_class = null
    ): string {
        if ($this->allGenericParameterSet()) {
            if (is_null($rights_controller)) {
                $rights_controller = $this->parent_controller;
            }
            if (is_null($right_id_name)) {
                $right_id_name = $this->parent_id_name;
            }
        }

        if ($this->genericTableParameterSet()) {
            if (is_null($result_class)) {
                $result_class = $this->class;
            }
        }

        if (isset($right_table) and is_null($right_id)) {
            $right_id = $this->getUrlParameter($right_table);
        }

        if (isset($right_id) and isset($rights_controller) and isset($right_id_name) and isset($result_class)) {
            if (is_null($right_id)) {
                $right_id = $this->getUrlParameter($right_table);
            }
            $role = $rights_controller::checkInstanceReadRights($right_id);
            if ($this->isAuthorized($role, $authorized_roles)) {
                $stmt->bindParam(":$right_id_name", $right_id);
                $stmt->execute();
                $result_data = $this->database->fetchAll($stmt);
                $result = [];
                foreach ($result_data as $result_item) {
                    array_push($result, new $result_class($result_item));
                }
                http_response_code(200);
                return json_encode($result);
            } else {
                http_response_code(404);
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => "User is not authorized to read $right_table."
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
     * @param string|null $parent_id Foreign key linked to the table that restricts the result set.
     * @param array $authorized_roles List of authorised roles.
     * @param PDOStatement|null $stmt Statement to be executed.
     * @param string|null $parent_table Database table name to which the database table is linked.
     * @param string|null $parent_id_name Name of the foreign key to which the database table is linked.
     * @param string|null $parent_controller Controller class of the foreign key relationship.
     * @return string Returns a json encoded list of all authorised entries in the specified database table.
     */
    public function readAllGeneric(
        ?string $parent_id = null,
        array $authorized_roles = [Role::MODERATOR, Role::FACILITATOR],
        ?PDOStatement $stmt = null,
        ?string $parent_table = null,
        ?string $parent_id_name = null,
        ?string $parent_controller = null
    ): string {
        if ($this->allGenericParameterSet()) {
            if (is_null($parent_table)) {
                $parent_table = $this->parent_table;
            }
            if (is_null($parent_id_name)) {
                $parent_id_name = $this->parent_id_name;
            }
            if (is_null($parent_controller)) {
                $parent_controller = $this->parent_controller;
            }
        }

        if ($this->genericTableParameterSet(
            ) and isset($parent_table) and isset($parent_id_name) and isset($parent_controller)) {
            if (is_null($parent_id)) {
                $parent_id = $this->getUrlParameter($parent_table);
            }
            $role = $parent_controller::checkInstanceReadRights($parent_id);
            if ($this->isAuthorized($role, $authorized_roles)) {
                if (is_null($stmt)) {
                    $query = "SELECT * FROM $this->table
            WHERE $parent_id_name = :$parent_id_name";
                    $stmt = $this->connection->prepare($query);
                }
                $stmt->bindParam(":$parent_id_name", $parent_id);
                $stmt->execute();
                $result_data = $this->database->fetchAll($stmt);
                $result = [];
                foreach ($result_data as $result_item) {
                    array_push($result, new $this->class($result_item));
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
                    "message" => "generic parameters not set"
                ]
            );
            die($error);
        }
    }

    /**
     * Get a specific entry in the specified database table.
     * @param string|null $id Primary key to be queried.
     * @param array $authorized_roles List of authorised roles.
     * @param PDOStatement|null $stmt Statement to be executed.
     * @param string|null $role User role.
     * @return string Returns a json encoded specific entry in the specified database table.
     */
    public function readGeneric(
        ?string $id = null,
        array $authorized_roles = [Role::MODERATOR, Role::FACILITATOR],
        ?PDOStatement $stmt = null,
        ?string $role = null
    ): string {
        if ($this->genericTableParameterSet()) {
            if (is_null($id)) {
                $id = $this->getUrlParameter($this->url_parameter);
            }
            if (is_null($role)) {
                $role = $this->checkReadRights($id);
            }
            if ($this->isAuthorized($role, $authorized_roles)) {
                if (is_null($stmt)) {
                    $query = "SELECT * FROM $this->table WHERE id = :id";
                    $stmt = $this->connection->prepare($query);
                }
                $stmt->bindParam(":id", $id);
                $stmt->execute();
                $result = $this->database->fetchFirst($stmt);
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
     * @param string|null $parent_id Foreign key linked to the table.
     * @param array|object $parameter Data to be inserted.
     * @param array $authorized_roles List of authorised roles.
     * @param bool $insert_id Table contains a uuid as primary key which should be generated automatically.
     * @param string|null $duplicate_check SQL statement which checks whether the data to be inserted is unique (if this is necessary).
     * @param array|object|null $parameter_dependencies Dependent data to be included.
     * @return string Inserted data in the json format.
     */
    public function addGeneric(
        ?string $parent_id,
        array|object $parameter,
        array $authorized_roles = [Role::MODERATOR],
        bool $insert_id = true,
        ?string $duplicate_check = "",
        array|object|null $parameter_dependencies = null
    ): string {
        if (isset($parent_id)) {
            if ($this->allGenericParameterSet()) {
                $role = $this->parent_controller::checkInstanceRights($parent_id);
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
            $role = $this->checkRights(null);
        }
        if (!$this->isAuthorized($role, $authorized_roles)) {
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
            if (!is_array($parameter_dependencies)) {
                $parameter_dependencies = [$parameter_dependencies];
            }

            foreach ($parameter as $param_index => $param_item) {
                $columns = null;
                $values = null;
                $bind_parameter = [];

                if ($insert_id) {
                    $id = self::uuid();

                    $columns = "id";
                    $values = ":id";
                    $bind_parameter[":id"] = $id;
                }
                foreach ($param_item as $key => $value) {
                    if (isset($columns)) {
                        $columns = "$columns, `$key`";
                        $values = "$values, :$key";
                    } else {
                        $columns = "`$key`";
                        $values = ":$key";
                    }
                    $bind_parameter[":$key"] = $value;
                }

                $query = "INSERT INTO $this->table
          ($columns)
          SELECT $values
          $duplicate_check ";
                $stmt = $this->connection->prepare($query);
                $stmt->execute($bind_parameter);
                $item_count = $stmt->rowCount();

                if ($item_count > 0) {
                    if ($insert_id) {
                        $this->addDependencies($id, $parameter_dependencies[$param_index]);
                    }
                }
            }

            $this->connection->commit();

            if ($item_count > 0) {
                http_response_code(200);
                if ($insert_id) {
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
            $error_msg = $e->getMessage();
            $this->connection->rollBack();
            $error = json_encode(
                [
                    "state" => "Failed",
                    "message" => 'Error occurred:' . $error_msg
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
     * @param array $authorized_roles List of authorised roles.
     * @return string Updated data in the json format.
     */
    public function updateGeneric(
        string $id,
        array|object $parameter,
        array $authorized_roles = [Role::MODERATOR]
    ): string {
        if ($this->genericTableParameterSet()) {
            $role = $this->checkRights($id);
            if (!$this->isAuthorized($role, $authorized_roles)) {
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

                $query_data = "";
                $bind_parameter = [":id" => $id];
                foreach ($parameter as $key => $value) {
                    if ($key != "id") {
                        $query_data .= "`$key` = NVL(:$key, `$key`), ";
                        $bind_parameter[":$key"] = $value;
                    }
                }
                $query_data = substr_replace($query_data, " ", -2);

                $query = "UPDATE $this->table SET
          $query_data
          WHERE id = :id";
                $stmt = $this->connection->prepare($query);
                $stmt->execute($bind_parameter);
                $item_count = $stmt->rowCount();
                $this->connection->commit();

                if ($item_count > 0) {
                    http_response_code(200);
                    $result = $this->read($id);
                    return $result;
                } else {
                    http_response_code(404);
                    $error = json_encode(
                        [
                            "state" => "Failed",
                            "message" => "no data was found to modify"
                        ]
                    );
                    die($error);
                }
            } catch (Exception $e) {
                http_response_code(404);
                $error_msg = $e->getMessage();
                $this->connection->rollBack();
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => 'Error occurred: ' . $error_msg
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
                    "message" => "generic parameters not set"
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
     * @param array $authorized_roles List of authorised roles.
     * @param PDOStatement|null $stmt Statement to be executed.
     * @return string Success status of the statement.
     */
    public function deleteGeneric(
        ?string $id = null,
        array $authorized_roles = [Role::MODERATOR],
        ?PDOStatement $stmt = null
    ): string {
        if ($this->genericTableParameterSet()) {
            if (is_null($id)) {
                $id = $this->getUrlParameter($this->url_parameter);
            }
            $role = $this->checkRights($id);
            if (!$this->isAuthorized($role, $authorized_roles)) {
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

            $handle_transaction = !$this->connection->inTransaction();
            try {
                if ($handle_transaction) {
                    $this->connection->beginTransaction();
                }

                $this->deleteDependencies($id);

                if (is_null($stmt)) {
                    $query = "DELETE FROM $this->table WHERE id = :id";
                    $stmt = $this->connection->prepare($query);
                    $stmt->bindParam(":id", $id);
                }
                $stmt->execute();
                $item_count = $stmt->rowCount();

                if ($handle_transaction) {
                    $this->connection->commit();
                }
            } catch (Exception $e) {
                http_response_code(404);
                $error_msg = $e->getMessage();
                $this->connection->rollBack();
                $error = json_encode(
                    [
                        "state" => "Failed",
                        "message" => 'Error occurred: ' . $error_msg
                    ]
                );
                die($error);
                #return $error;
            }

            if ($item_count > 0) {
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
                        "message" => "$this->table not found"
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
     * @param string $role user role
     * @param array $authorized_roles List of authorised roles.
     * @return bool Authorisation state
     */
    public function isAuthorized(?string $role, array $authorized_roles = array(Role::MODERATOR)): bool
    {
      if (isset($role)) {
        $role = strtoupper($role);
        $authorized_roles = array_map("strtoupper", $authorized_roles);
        return in_array($role, $authorized_roles);
      }
      return false;
    }

    /**
     * Checks whether the user is authorised to edit the entry with the specified primary key.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public function checkRights(?string $id): ?string
    {
        if (is_null($id)) {
            $login_id = Authorization::getAuthorizationProperty("login_id");
            return strtoupper(Role::MODERATOR);
        }

        if ($this->allGenericParameterSet()) {
            $query = "SELECT * FROM $this->table WHERE id = :id";
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $item_count = $stmt->rowCount();
            if ($item_count > 0) {
                $result = $this->database->fetchFirst($stmt);
                $parent_id = $result[$this->parent_id_name];
                return $this->parent_controller::checkInstanceRights($parent_id);
            }
        }
        return null;
    }

    /**
     * Check whether your own user data is being edited.
     * @param string|null $id Primary key to be checked.
     * @return string Role with which the user is authorised to access the entry.
     */
    public function checkLogin(?string $id): string
    {
        if (Authorization::isLoggedIn()) {
            if (Authorization::isUser()) {
                $login_id = Authorization::getAuthorizationProperty("login_id");
                if (is_null($id) or $id == $login_id) {
                    return Role::MODERATOR;
                }
            }
            if (Authorization::isParticipant()) {
                $participant_id = Authorization::getAuthorizationProperty("participant_id");
                if (is_null($id) or $id == $participant_id) {
                    return Role::PARTICIPANT;
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
        return $this->checkRights($id);
    }

    /**
     * Check the edit rights of the static singleton instance.
     * @param string|null $id Primary key to be checked.
     * @return string|null Role with which the user is authorised to access the entry.
     */
    public static function checkInstanceRights(?string $id): ?string
    {
        $instance = self::getInstance();
        return $instance->checkRights($id);
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
     * @param string $parameter_name Name of the parameter. The name is separated by a slash before the parameter value ($parameter_name/$parameter_value).
     * @param mixed|null $default_value Default value if no parameter entry ist found.
     * @return mixed Returns the parameter value.
     */
    public static function getUrlParameter(string $parameter_name, mixed $default_value = null): mixed
    {
        $parameter_value = $default_value;
        if (count($_GET) > 0 and array_key_exists($parameter_name, $_GET)) {
            $parameter_value = $_GET[$parameter_name];
        } else {
            $url_parts = explode("/", $_SERVER["REQUEST_URI"]);
            $url_part_count = count($url_parts);
            for ($index = $url_part_count - 1; $index > 0; $index--) {
                $test_item = $url_parts[$index];
                if (strlen($test_item) > 0) {
                    $parameter_description = $url_parts[$index - 1];
                    if ($parameter_description == $parameter_name) {
                        $parameter_value = $test_item;
                        break;
                    }
                }
            }
        }
        return $parameter_value;
    }

    /**
     * Get a list with all url parameters.
     * @param int $first_param_index Start index where to start the search for url parameters.
     * @return object Returns an object with all url parameters.
     */
    public static function getUrlParameters(int $first_param_index = 1): object
    {
        $params = [];
        foreach ($_GET as $key => $value) {
            $params[$key] = $value;
        }

        $url_parts = explode("/", $_SERVER["REQUEST_URI"]);
        $url_part_count = count($url_parts);
        $api_index = 0;
        for ($index = 0; $index < $url_part_count; $index++) {
            $test_item = $url_parts[$index];
            if ($test_item == "api") {
                $api_index = $index;
                break;
            }
        }

        for ($index = $api_index + $first_param_index; $index < $url_part_count; $index += 2) {
            $test_item = $url_parts[$index];
            if (strlen($test_item) > 0 and $index + 1 < $url_part_count) {
                $params[$test_item] = $url_parts[$index + 1];
            }
        }

        return (object)$params;
    }

    /**
     * Break down url hierarchy.
     * @param int $first_param_index Start index where to start the search for url parameters.
     * @return array Returns a list of the url hierarchy.
     */
    public static function getUrlHierarchy(int $first_param_index = 1): array
    {
        $hierarchy = [];

        $url_parts = explode("/", $_SERVER["REQUEST_URI"]);
        $url_part_count = count($url_parts);
        $api_index = 0;
        for ($index = 0; $index < $url_part_count; $index++) {
            $test_item = $url_parts[$index];
            if ($test_item == "api") {
                $api_index = $index;
                break;
            }
        }
        $api_index += 1;
        $first_param_index -= 1;

        for ($index = $api_index; $index < $api_index + $first_param_index; $index++) {
            $test_item = $url_parts[$index];
            if (strlen($test_item) > 0) {
                array_push($hierarchy, $test_item);
            }
        }

        for ($index = $api_index + $first_param_index; $index < $url_part_count; $index += 2) {
            $test_item = $url_parts[$index];
            if (strlen($test_item) > 0) {
                array_push($hierarchy, $test_item);
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
        $param_data = [];
        foreach ($parameter as $key => $key_definition) {
            $key_type = null;
            $key_result = null;
            $value = null;
            if (isset($key_definition)) {
                $key_definition = (object)$key_definition;
                if (isset($key_definition->type)) {
                    $key_type = $key_definition->type;
                }
                if (isset($key_definition->result)) {
                    $key_result = $key_definition->result;
                }
                if (isset($key_definition->default)) {
                    $value = $key_definition->default;
                }
                if (is_null($value) and isset($key_definition->url)) {
                    $value = self::getUrlParameter($key_definition->url);
                }
            }

            if (isset($key_result) and $key_result == "all") {
                $value = self::getBodyParameter(null);
            }

            if (is_null($value)) {
                $value = self::getBodyParameter($key);
            }

            if (isset($value) and isset($key_type)) {
                if ($key_type == "JSON") {
                    $value = json_encode((object)$value);
                } elseif ($key_type == "ARRAY") {
                    $value = $value;
                } elseif ($key_type == "MD5") {
                    $value = md5($value);
                } else {
                    $value = strtoupper($value);
                    if (!defined("$key_type::$value")) {
                        http_response_code(404);
                        $error = json_encode(
                            [
                                "state" => "wrong $key_type",
                                "message" => "the specified $key_type does not exist"
                            ]
                        );
                        die($error);
                    }
                }
            }
            $param_data[$key] = $value;
        }

        return (object)$param_data;
    }

    /**
     * Checks if this is the REST call you are looking for.
     * @param string $search_method Request type.
     * @param int $first_param_index Start index where to start the search for url parameters.
     * @param string $search_detail_hierarchy Break down url hierarchy.
     * @return bool Returns if this is the REST call you are looking for.
     */
    public static function isRestCall(
        string $search_method,
        int $first_param_index = 1,
        string $search_detail_hierarchy = ""
    ): bool {
        $url_hierarchy = self::getUrlHierarchy($first_param_index);

        $search_hierarchy_parts = [];
        for ($i = 0; $i < 1; $i++) {
            array_push($search_hierarchy_parts, $url_hierarchy[$i]);
        }

        $search_hierarchy_parts = array_merge($search_hierarchy_parts, explode("/", $search_detail_hierarchy));

        for ($index = 0; $index < count($search_hierarchy_parts); $index++) {
            if (strlen($search_hierarchy_parts[$index]) == 0) {
                unset($search_hierarchy_parts[$index]);
            }
        }

        if (count($search_hierarchy_parts) == count($url_hierarchy)) {
            if ($search_hierarchy_parts == $url_hierarchy) {
                if ($_SERVER["REQUEST_METHOD"] == $search_method) {
                    return true;
                }
            }
        }
        return false;
    }
}
