<?php

namespace PieLab\GAB\Config;

use PDO;
use PDOException;
use PDOStatement;

/**
 * This class provides the single access point to the Database.
 */
class Database
{
    /**
     * The singleton Database instance.
     * @var Database
     */
    private static Database $instance;

    /**
     * The database hostname.
     * @var string
     */
    private string $host;

    /**
     * The database name.
     * @var string
     */
    private string $name;

    /**
     * The username used to connect to the database.
     * @var string
     */
    private string $username;

    /**
     * The password used to connect to the database.
     * @var string
     */
    private string $password;

    /**
     * The actual database connection using PDO.
     * @var PDO|null
     */
    private ?PDO $connection;

    /**
     * Creates a new Database object using the data stored in the Settings object.
     */
    private function __construct()
    {
        $settings = Settings::getInstance();
        $databaseSettings = $settings["database"];

        $this->host = $databaseSettings["host"];
        $this->name = $databaseSettings["name"];
        $this->username = $databaseSettings["username"];
        $this->password = $databaseSettings["password"];

        $this->connection = null;

        //$this->host = $_SERVER['HTTP_HOST'];

        try {
            $dsn = "mysql:host=$this->host; dbname=$this->name;";
            $this->connection = new PDO($dsn, $this->username, $this->password);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Database could not be connected: " . $exception->getMessage();
        }
    }

    /**
     * Creates a single instance for a database connection.
     * @return static The Database object.
     */
    public static function getInstance(): static
    {
        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    /**
     * Get the database connection.
     * @return PDO Returns the database connection.
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Returns an array containing all of the result set rows.
     * @param PDOStatement $statement Statement to be executed.
     * @return array Returns all result set rows.
     */
    public function fetchAll(PDOStatement $statement): array
    {
        $itemCount = $statement->rowCount();

        if ($itemCount > 0) {
            #http_response_code(200);
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } else {
            return [];
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
     * @param PDOStatement $statement Statement to be executed.
     * @param string $errorMessage Error message to be thrown if no data is found.
     * @return mixed Returns the first result set rows.
     */
    public function fetchFirst(PDOStatement $statement, string $errorMessage = "No records found."): mixed
    {
        $itemCount = $statement->rowCount();

        if ($itemCount > 0) {
            #http_response_code(200);
            return $statement->fetch(PDO::FETCH_ASSOC);
            #return json_encode(
            #  array(
            #    "response"=>$stmt->fetch(PDO::FETCH_ASSOC),
            #    "count"=>$itemCount
            #  )
            #);
        } else {
            http_response_code(404);
            $error = json_encode(
                array(
                    "state" => "Failed",
                    "message" => $errorMessage
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
    public function checkSuccess(): array
    {
        $databaseErrors = $this->connection->errorInfo();
        foreach ($databaseErrors as $key => $value) {
            echo $key . ": " . $value . "\n";
        }

        if (!empty($databaseErrors)) {
            http_response_code(404);
            $error = [
                "state" => "Failed",
                "message" => "Error occurred:" . implode(":", $databaseErrors)
            ];
            die($error);
            #return $error;
        } else {
            return [
                "state" => "Success",
                "message" => "No database errors occurred."
            ];
        }
    }
}
