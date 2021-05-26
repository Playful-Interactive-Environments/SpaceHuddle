<?php
require_once('AuthorizedUserTestCase.php');

class SessionTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_session_id();
  }

  public function test_get_all() {
    $res = $this->client->get($this->get_absolute_api_url("/api/sessions/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_by_id() {
    $this->assertIsString($this->session_id);
    $res = $this->client->get($this->get_absolute_api_url("/api/session/$this->session_id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_workflow() {
    $data =  json_encode((object)array(
      'title' => 'test session',
      'max_participants' => 100,
      'expiration_date' => '2021-12-31'
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/session/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'title' => 'test session 2',
      'max_participants' => 99
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/session/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/session/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
