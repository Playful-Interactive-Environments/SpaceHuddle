<?php
require_once('AuthorizedUserTestCase.php');

use PieLab\GAB\Models\TaskType;
use PieLab\GAB\Models\StateTask;

class TaskTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTopicId();
    $this->getFirstTaskIdIdea();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/topic/$this->topicId/tasks/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->taskIdIdea);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$this->taskIdIdea/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'taskType' => TaskType::SELECTION,
      'name' => 'test selection',
      'parameter' => "{'count': 10}",
      'order' => 10
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/topic/$this->topicId/task/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'taskType' => TaskType::SELECTION,
      'name' => 'test selection',
      'parameter' => "{'count': 10}",
      'order' => 10,
      'state' => StateTask::READ_ONLY
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/task/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/task/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
