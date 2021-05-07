<?php
require_once(__DIR__.'/../config/database.php');

class Controller
{
  #protected static $instance  = null;
  protected static $instances;
  protected $database;
  protected $connection;

  public function __construct()
  {
    $this->database = Database::get_instance();
    $this->connection = $this->database->get_connection();
  }

  public static function get_instance() {
      $class = get_called_class();

      if (!isset(self::$instances[$class])) {
          self::$instances[$class] = new $class;
      }
      return self::$instances[$class];
  }

  /*
  public static function get_instance () {
      if (is_null(self::$instance)) {
        self::$instance = new static();
      }
      return self::$instance;
  }
  */

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
    if (array_key_exists($parameter_name, $data)) {
      return $data[$parameter_name];
    }
    return $default_value;
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
