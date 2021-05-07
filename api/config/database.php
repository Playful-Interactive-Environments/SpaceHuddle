<?php

class Database
{
  private static $instance = null;
  private $host = "localhost";
  private $database_name = "gab";
  private $username = "root";
  private $password = "";

  private $connection;

  public function __construct()
  {
    $this->connection = null;
    try {
      $this->connection = new PDO (
        'mysql:host='.$this->host.'; dbname='.$this->database_name.';',
        $this->username,
        $this->password);
      $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $exception) {
      echo "Database could not be connected: ".$exception->get_message();
    }
  }

  public static function get_instance () {
      if (is_null(self::$instance)) {
        self::$instance = new Database();
      }
      return self::$instance;
  }

  public function get_connection() {
    return $this->connection;
  }

  public function fatch_all($stmt) {
    $item_count = $stmt->rowCount();

    if ($item_count > 0) {
      http_response_code(200);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    else {
      return array();
      /*
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>"No records found."
        )
      );
      die($error);
      #return $error;
      */
    }
  }

  public function fatch_first($stmt, $error_msg = "No records found.") {
    $item_count = $stmt->rowCount();

    if ($item_count > 0) {
      http_response_code(200);
      return $stmt->fetch(PDO::FETCH_ASSOC);
      #return json_encode(
      #  array(
      #    "response"=>$stmt->fetch(PDO::FETCH_ASSOC),
      #    "count"=>$item_count
      #  )
      #);
    }
    else {
      http_response_code(404);
      $error = json_encode(
        array(
          "state"=>"Failed",
          "message"=>$error_msg
        )
      );
      die($error);
      #return $error;
    }
  }

  public function check_success() {
    $databaseErrors = $this->connection->errorInfo();
    foreach ($databaseErrors as $key => $value) {
  		echo $key.": ".$value."\n";
  	}

    if( !empty($databaseErrors) ){
      http_response_code(404);
      $error = array(
        "state"=>"Failed",
        "message"=>'Error occurred:'.implode(":",$databaseErrors)
      );
      die($error);
      #return $error;
    }
    else {
      return array(
        "state"=>"Success",
      );
    }
  }
}

?>
