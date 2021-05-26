<?php
use PHPUnit\Framework\TestCase;

abstract class AuthorizedTestCase extends TestCase
{
  protected $client;
  protected $host = "localhost";

  public function __construct()
  {
    parent::__construct();
    #$this->host = $_SERVER['HTTP_HOST'];
    $access_token =  $this->get_access_token();
    if (isset($access_token)) {
      $bearer = "Bearer $access_token";
      $this->client = new GuzzleHttp\Client([
          'headers' => ["Authorization" => $bearer]
      ]);
    }
    else {
      $this->client = new GuzzleHttp\Client();
    }
  }

  protected function get_absolute_api_url($relative_url) {
    return "http://$this->host/$relative_url";
  }

  protected function get_access_token() {
    return null;
  }

  protected function to_json($data) {
    if ($this->is_json($data))
      return json_decode($data);
    return null;
  }

  protected function is_json($data) {
    $is_json = is_array(json_decode($data, true));
    if (json_last_error() != JSON_ERROR_NONE) {
      $is_json = false;
      $error = json_last_error_msg();
      echo "\n\nJSON Error: $error\n\n";
    }
    return $is_json;
  }

  protected function assertIsJSON($data) {
    $this->assertTrue($this->is_json($data));
  }
}

?>
