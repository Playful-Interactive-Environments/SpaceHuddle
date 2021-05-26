<?php
require_once('AuthorizedTestCase.php');

class LoginTest extends AuthorizedTestCase
{
  public function test_workflow() {
    $data =  json_encode((object)array(
      'username' => 'temp',
      'password' => 'string',
      'password_confirmation' => 'string'
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/user/register/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $login_data =  json_encode((object)array(
      'username' => 'temp',
      'password' => 'string'
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/user/login/"), [
        'body' => $login_data
    ]);
    $access_token = $this->to_json($res->getBody())->access_token;
    $bearer = "Bearer $access_token";

    $data =  json_encode((object)array(
      'old_password' => 'string',
      'password' => 'new',
      'password_confirmation' => 'new'
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/user/"), [
      'headers' => ["Authorization" => $bearer],
      'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/user/"), [
      'headers' => ["Authorization" => $bearer]
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
