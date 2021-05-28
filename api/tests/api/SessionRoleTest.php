<?php
require_once('AuthorizedUserTestCase.php');
use PieLab\GAB\Models\Role;

class SessionRoleTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstSessionId();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$this->sessionId/authorized_users/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$this->sessionId/own_user_role/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $username = "xxx";
    $data =  json_encode((object)array(
      'username' => $username,
      'role' => Role::FACILITATOR
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/session/$this->sessionId/authorized_users/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $data =  json_encode((object)array(
        'username' => $username,
        'role' => Role::PARTICIPANT
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/session/$this->sessionId/authorized_users/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/session/$this->sessionId/authorized_users/$username/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
