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
    $accessToken =  $this->getAccessToken();
    if (isset($accessToken)) {
      $bearer = "Bearer $accessToken";
      $this->client = new GuzzleHttp\Client([
          'headers' => ["Authorization" => $bearer]
      ]);
    }
    else {
      $this->client = new GuzzleHttp\Client();
    }
  }

  protected function getAbsoluteApiUrl($relative_url) : string {
    return "http://$this->host/$relative_url";
  }

  protected function getAccessToken() : ?string {
    return null;
  }

  protected function toJSON($data) : mixed {
    if ($this->is_json($data))
      return json_decode($data);
    return null;
  }

  protected function is_json($data) : bool {
    $isJson = is_array(json_decode($data, true));
    if (json_last_error() != JSON_ERROR_NONE) {
      $isJson = false;
      $error = json_last_error_msg();
      echo "\n\nJSON Error: $error\n\n";
    }
    return $isJson;
  }

  protected function assertIsJSON($data) {
    $this->assertTrue($this->is_json($data));
  }
}

?>
