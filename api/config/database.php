<?php

/**
 * Class Database standardises the database access.
 */
class Database
{
  private static ?Database $instance = null;
  private string $host = "localhost";
  private string $database_name = "gab";
  private string $username = "root";
  private string $password = "";

  private ?PDO $connection;

  public function __construct()
  {
    $this->host = $_SERVER['HTTP_HOST'];
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

  /**
   * singleton pattern
   * @return Database Returns a static instance for database access.
   */
  public static function getInstance () : Database {
      if (is_null(self::$instance)) {
        self::$instance = new Database();
      }
      return self::$instance;
  }

  /**
   * Get the database connection.
   * @return PDO Returns the database connection.
   */
  public function getConnection() : PDO {
    return $this->connection;
  }

  /**
   * Returns an array containing all of the result set rows.
   * @param PDOStatement $stmt Statement to be executed.
   * @return array Returns all result set rows.
   */
  public function fetchAll(PDOStatement $stmt) : array {
    $item_count = $stmt->rowCount();

    if ($item_count > 0) {
      #http_response_code(200);
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

  /**
   * Returns the first result set rows.
   * @param PDOStatement $stmt Statement to be executed.
   * @param string $error_msg Error message to be thrown if no data is found.
   * @return mixed Returns the first result set rows.
   */
  public function fetchFirst(
      PDOStatement $stmt,
      string $error_msg = "No records found."
  ) : mixed {
    $item_count = $stmt->rowCount();

    if ($item_count > 0) {
      #http_response_code(200);
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

  /**
   * Checks whether the last database statement could be executed successfully.
   * @return string[] Status of the last database statement.
   */
  public function checkSuccess() : array {
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
