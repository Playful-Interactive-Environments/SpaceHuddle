<?php
require_once('AuthorizedTestCase.php');

class LoginTest extends AuthorizedTestCase
{
  public function testWorkflow() {
    $data =  json_encode((object)array(
      'username' => 'temp',
      'password' => 'string',
      'passwordConfirmation' => 'string'
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/user/register/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $login_data =  json_encode((object)array(
      'username' => 'temp',
      'password' => 'string'
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/user/login/"), [
        'body' => $login_data
    ]);
    $accessToken = $this->toJSON($res->getBody())->accessToken;
    $bearer = "Bearer $accessToken";

    $data =  json_encode((object)array(
      'oldPassword' => 'string',
      'password' => 'new',
      'passwordConfirmation' => 'new'
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/user/"), [
      'headers' => ["Authorization" => $bearer],
      'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/user/"), [
      'headers' => ["Authorization" => $bearer]
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
