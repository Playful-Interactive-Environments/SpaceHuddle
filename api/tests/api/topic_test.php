<?php
require_once('authorized_user_test_case.php');

class Topic_Test extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_session_id();
    $this->get_first_topic_id();
  }

  public function test_get_all() {
    $res = $this->client->get($this->get_absolute_api_url("/api/session/$this->session_id/topics/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_by_id() {
    $this->assertIsString($this->topic_id);
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$this->topic_id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_workflow() {
    $data =  json_encode((object)array(
      'title' => 'test topic',
      'description' => '...'
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/session/$this->session_id/topic/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'title' => 'test topic',
      'description' => 'new description',
      'active_task_id' => null
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/topic/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/topic/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
