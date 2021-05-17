<?php
use Ramsey\Uuid\Uuid;
require_once(__DIR__.'/../config/database.php');

class Controller
{
  #protected static $instance  = null;
  protected static $instances;
  protected $database;
  protected $connection;
  protected $table;
  protected $class;
  protected $parent_controller;
  protected $parent_table;
  protected $parent_id_name;
  protected $url_parameter;

  public function __construct($table = null, $class = null, $parent_controller = null, $parent_table = null, $parent_id_name = null, $url_parameter = null)
  {
    $this->database = Database::get_instance();
    $this->connection = $this->database->get_connection();
    $this->class = $class;
    $this->table = $table;
    $this->parent_controller = $parent_controller;
    $this->parent_table = $parent_table;
    $this->parent_id_name = $parent_id_name;
    if (is_null($url_parameter)) $url_parameter = $table;
    $this->url_parameter = $url_parameter;
  }

  protected function all_generic_parameter_set() {
    return (
      isset($this->table) and
      isset($this->url_parameter) and
      isset($this->class) and
      isset($this->parent_controller) and
      isset($this->parent_table) and
      isset($this->parent_id_name)
    );
  }

  protected function generic_table_parameter_set() {
    return (
      isset($this->table) and
      isset($this->url_parameter) and
      isset($this->class)
    );
  }

  public static function get_instance() {
      $class = get_called_class();

      if (!isset(self::$instances[$class])) {
          self::$instances[$class] = new $class;
      }
      return self::$instances[$class];
  }

  public function read_all_generic(
    $parent_id = null,
    $authorized_roles = array(Role::MODERATOR, Role::FACILITATOR),
    $stmt = null,
    $parent_table = null,
    $parent_id_name = null,
    $parent_controller = null
  )  {
    if ($this->all_generic_parameter_set()) {
      if (is_null($parent_table))
        $parent_table = $this->parent_table;
      if (is_null($parent_id_name))
        $parent_id_name = $this->parent_id_name;
      if (is_null($parent_controller))
        $parent_controller = $this->parent_controller;

      if (is_null($parent_id)) {
        $parent_id = $this->get_url_parameter($parent_table);
      }
      $role = $parent_controller::check_instance_read_rights($parent_id);
      if ($this->is_authorized($role, $authorized_roles)) {
        if (is_null($stmt)) {
          $query = "SELECT * FROM $this->table
            WHERE $parent_id_name = :$parent_id_name";
          $stmt = $this->connection->prepare($query);
        }
        $stmt->bindParam(":$parent_id_name", $parent_id);
        $stmt->execute();
        $result_data = $this->database->fatch_all($stmt);
        $result = array();
        foreach($result_data as $result_item) {
          array_push($result, new $this->class($result_item));
        }
        return json_encode($result);
      }
      else {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>"User is not authorized to read $this->table."
          )
        );
        die($error);
      }
    }
  }

  public function read_generic($id = null, $authorized_roles = array(Role::MODERATOR, Role::FACILITATOR), $stmt = null)  {
    if ($this->generic_table_parameter_set()) {
      if (is_null($id)) {
        $id = $this->get_url_parameter($this->url_parameter);
      }
      $role = $this->check_read_rights($id);
      if ($this->is_authorized($role, $authorized_roles)) {
        if (is_null($stmt)) {
          $query = "SELECT * FROM $this->table WHERE id = :id";
          $stmt = $this->connection->prepare($query);
        }
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $result = $this->database->fatch_first($stmt);
        return json_encode(new $this->class($result));
      }
      else {
        http_response_code(404);
        $error = json_encode(
          array(
            "state"=>"Failed",
            "message"=>"User is not authorized to read $this->table."
          )
        );
        die($error);
      }
    }
  }

  public function add_generic($parent_id, $parameter, $authorized_roles = array(Role::MODERATOR), $insert_id = true, $duplicate_check = "")  {
    if ($this->all_generic_parameter_set()) {
      $role = $this->parent_controller::check_instance_rights($parent_id);
      if (!$this->is_authorized($role, $authorized_roles)) {
          http_response_code(404);
          $error = json_encode(
            array(
              "state"=>"Failed",
              "message"=>"User is not authorized to add this $this->table."
            )
          );
          die($error);
      }

      try{
        $this->connection->beginTransaction();
        if (!is_array($parameter)) {
          $parameter = array($parameter);
        }

        foreach ($parameter as $param_item) {
          $columns = null;
          $values = null;
          $bind_parameter = array();

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
            }
            else {
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
        }

        $this->connection->commit();
        if ($insert_id) {
          $result = $this->read($id);
          return $result;
        }
      }
      catch(Exception $e){
          http_response_code(404);
          $error_msg = $e->getMessage();
          $this->connection->rollBack();
          $error = json_encode(
            array(
              "state"=>"Failed",
              "message"=>'Error occurred:'.$error_msg
            )
          );
          die($error);
          #return $error;
      }
    }
  }

  public function update_generic($id, $parameter, $authorized_roles = array(Role::MODERATOR))  {
    if ($this->generic_table_parameter_set()) {
      $role = $this->check_rights($id);
      if (!$this->is_authorized($role, $authorized_roles)) {
          http_response_code(404);
          $error = json_encode(
            array(
              "state"=>"Failed",
              "message"=>"User is not authorized to update this $this->table."
            )
          );
          die($error);
      }

      try{
        $this->connection->beginTransaction();

        $query_data = "";
        $bind_parameter = array(":id"=>$id);
        foreach ($parameter as $key => $value) {
          if ($key != "id") {
            $query_data .= "`$key` = NVL(:$key, `$key`), ";
            $bind_parameter[":$key"] = $value;
          }
      	}
        $query_data = substr_replace($query_data ," ", -2);

        $query = "UPDATE $this->table SET
          $query_data
          WHERE id = :id";
        $stmt = $this->connection->prepare($query);
        $stmt->execute($bind_parameter);
        $this->connection->commit();
        $result = $this->read($id);
        return $result;
      }
      catch(Exception $e){
          http_response_code(404);
          $error_msg = $e->getMessage();
          $this->connection->rollBack();
          $error = json_encode(
            array(
              "state"=>"Failed",
              "message"=>'Error occurred: '.$error_msg
            )
          );
          die($error);
          #return $error;
      }
    }
  }

  public function delete_dependencies($id) {

  }

  public function delete_generic($id = null, $authorized_roles = array(Role::MODERATOR), $stmt = null, $role = null)  {
    if ($this->generic_table_parameter_set()) {
      if (is_null($id)) {
        $id = $this->get_url_parameter($this->url_parameter);
      }
      if (is_null($role))
        $role = $this->check_rights($id);
      if (!$this->is_authorized($role, $authorized_roles)) {
          http_response_code(404);
          $error = json_encode(
            array(
              "state"=>"Failed",
              "message"=>"User is not authorized to delete this $this->table."
            )
          );
          die($error);
          #return $error;
      }

      $handle_transaction = !$this->connection->inTransaction();
      try{
        if ($handle_transaction)
          $this->connection->beginTransaction();

        $this->delete_dependencies($id);

        if (is_null($stmt)) {
          $query = "DELETE FROM $this->table WHERE id = :id";
          $stmt = $this->connection->prepare($query);
          $stmt->bindParam(":id", $id);
        }
        $stmt->execute();

        if ($handle_transaction)
          $this->connection->commit();
      }
      catch(Exception $e){
          http_response_code(404);
          $error_msg = $e->getMessage();
          $this->connection->rollBack();
          $error = json_encode(
            array(
              "state"=>"Failed",
              "message"=>'Error occurred: '.$error_msg
            )
          );
          die($error);
          #return $error;
      }

      return json_encode(
        array(
          "state"=>"Sccess",
          "message"=>"$this->table was successful deleted"
        )
      );
    }
  }

  public function is_authorized($role, $authorized_roles = array(Role::MODERATOR))  {
    $role = strtoupper($role);
    $authorized_roles = array_map("strtoupper", $authorized_roles);
    return in_array($role, $authorized_roles);
  }

  public function check_rights($id) {
    if ($this->all_generic_parameter_set()) {
      $query = "SELECT * FROM $this->table WHERE id = :id";
      $stmt = $this->connection->prepare($query);
      $stmt->bindParam(":id", $id);
      $stmt->execute();
      $item_count = $stmt->rowCount();
      if ($item_count > 0) {
        $result = $this->database->fatch_first($stmt);
        $parent_id = $result[$this->parent_id_name];
        $role = $this->parent_controller::check_instance_rights($parent_id);
        return $role;
      }
    }
    return null;
  }

  public function check_read_rights($id) {
    return $this->check_rights($id);
  }

  public static function check_instance_rights($id) {
    $instance = self::get_instance();
    return $instance->check_rights($id);
  }

  public static function check_instance_read_rights($id) {
    $instance = self::get_instance();
    return $instance->check_read_rights($id);
  }

  public static function uuid() {
  	$uuid = Uuid::uuid4();
  	return $uuid->toString();
  }

  public static function get_url_parameter($parameter_name,  $default_value = null) {
    $parameter_value = $default_value;
    if (count($_GET) > 0 and array_key_exists($parameter_name, $_GET)) {
    	$parameter_value = $_GET[$parameter_name];
    }
    else {
    	$url_parts = explode("/", $_SERVER["REQUEST_URI"]);
      $url_part_count = count($url_parts);
      for ($index = $url_part_count-1; $index > 0; $index--) {
        $test_item = $url_parts[$index];
        if (strlen($test_item) > 0) {
          $parameter_description = $url_parts[$index-1];
          if ($parameter_description == $parameter_name) {
            $parameter_value = $test_item;
            break;
          }
        }
      }
    }
    return $parameter_value;
  }

  public static function get_url_parameters($first_param_index = 1) {
    $params = array();
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

    for ($index = $api_index+$first_param_index; $index < $url_part_count; $index+=2) {
      $test_item = $url_parts[$index];
      if (strlen($test_item) > 0 and $index + 1 < $url_part_count) {
        $params[$test_item] = $url_parts[$index + 1];
      }
    }

    return (object)$params;
  }

  public static function get_url_hierarchy($first_param_index = 1) {
    $hierarchy = array();

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

    for ($index = $api_index+1; $index < $api_index+$first_param_index; $index++) {
      $test_item = $url_parts[$index];
      if (strlen($test_item) > 0) {
        array_push($hierarchy, $test_item);
      }
    }

    for ($index = $api_index+$first_param_index; $index < $url_part_count; $index+=2) {
      $test_item = $url_parts[$index];
      if (strlen($test_item) > 0) {
        array_push($hierarchy, $test_item);
      }
    }

    return $hierarchy;
  }

  public static function get_body_parameter($parameter_name,  $default_value = null) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"data body incorrectly formatted",
          "message"=>"Data body cannot be read. JSON is not valid."
        )
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

  public static function get_all_body_parameter() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"data body incorrectly formatted",
          "message"=>"Data body cannot be read. JSON is not valid."
        )
      );
      die($error);
    }
    return $data;
  }

  public static function format_parameters($parameter) {
    $param_data = array();
    foreach ($parameter as $key => $key_definition) {
      $key_type = null;
      $key_result = null;
      $value = null;
      if (isset($key_definition)) {
        $key_definition = (object)$key_definition;
        if (isset($key_definition->type))
          $key_type = $key_definition->type;
        if (isset($key_definition->result))
          $key_result = $key_definition->result;
        if (isset($key_definition->default))
          $value = $key_definition->default;
        if (is_null($value) and isset($key_definition->url))
          $value = self::get_url_parameter($key_definition->url);
      }

      if (isset($key_result) and $key_result == "all") {
        $value = self::get_body_parameter(null);
      }

      if (is_null($value))
        $value = self::get_body_parameter($key);

      if (isset($value) and isset($key_type)) {
        if ($key_type == "JSON") {
          $value = json_encode((object)$value);
        }
        elseif ($key_type == "ARRAY") {
          $value = $value;
        }
        else {
          $value = strtoupper($value);
          if (!defined("$key_type::$value")) {
              http_response_code(404);
              $error = json_encode(
                array(
                  "state"=>"wrong $key_type",
                  "message"=>"the specified $key_type does not exist"
                )
              );
              die($error);
          }
        }
      }
      $param_data[$key] = $value;
    }

    return (object)$param_data;
  }

  public static function is_rest_call($search_method, $first_param_index = 1, $search_detail_hierarchy = "") {
    $url_hierarchy = self::get_url_hierarchy($first_param_index);

    $search_hierarchy_parts = array();
    for ($i=0; $i < $first_param_index; $i++) {
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
?>
