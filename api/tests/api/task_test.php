<?php
require_once('authorized_user_test_case.php');
require_once(__DIR__.'/../../models/task_type.php');
require_once(__DIR__.'/../../models/state.php');

class Task_Test extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->get_first_topic_id();
    $this->get_first_task_id_idea();
  }

  public function test_get_all() {
    $res = $this->client->get($this->get_absolute_api_url("/api/topic/$this->topic_id/tasks/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_get_by_id() {
    $this->assertIsString($this->task_id_idea);
    $res = $this->client->get($this->get_absolute_api_url("/api/task/$this->task_id_idea/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function test_workflow() {
    $data =  json_encode((object)array(
      'task_type' => Task_Type::SELECTION,
      'name' => 'test selection',
      'parameter' => "{'count': 10}",
      'order' => 10
    ));

    $res = $this->client->post($this->get_absolute_api_url("/api/topic/$this->topic_id/task/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->to_json($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'task_type' => Task_Type::SELECTION,
      'name' => 'test selection',
      'parameter' => "{'count': 10}",
      'order' => 10,
      'state' => State_Task::READ_ONLY
    ));

    $res = $this->client->put($this->get_absolute_api_url("/api/task/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->get_absolute_api_url("/api/task/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
