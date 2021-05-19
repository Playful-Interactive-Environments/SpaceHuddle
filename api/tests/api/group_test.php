<?php
require_once('authorized_user_test_case.php');

class Group_Test extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_topic_id();
    $this->get_first_task_id_group();
    $this->get_first_group_id();
    $this->get_first_idea_id();
  }

  public function test_get_all() {
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$this->topic_id/groups/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    $res = $this->client->get($this->get_absolute_api_url("/api/task/$this->task_id_group/groups/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_by_id() {
    $this->assertIsString($this->group_id);
    $res = $this->client->get($this->get_absolute_api_url("/api/group/$this->group_id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_group_ideas() {
    $res = $this->client->get($this->get_absolute_api_url("/api/group/$this->group_id/ideas/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_workflow() {
    $data =  json_encode((object)array(
      'keywords' => 'test group',
      'description' => "..."
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/topic/$this->topic_id/group/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $res = $this->client->delete($this->get_absolute_api_url("/api/group/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->post($this->get_absolute_api_url("/api/task/$this->task_id_group/group/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'keywords' => 'test group',
      'description' => "new description"
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/group/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/group/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_workflow_group_ideas() {
    $data =  json_encode(array(
      $this->idea_id
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/group/$this->group_id/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/group/$this->group_id/ideas/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
