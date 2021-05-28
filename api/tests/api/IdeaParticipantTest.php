<?php
require_once('AuthorizedParticipantTestCase.php');
use PieLab\GAB\Models\StateIdea;

class IdeaParticipantTest extends AuthorizedParticipantTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTopicId();
    $this->getFirstTaskIdIdea();
    #$user = new Idea_Test();
    #$this->topic_id = $user->get_first_topic_id();
    #$this->task_id_idea = $user->get_first_task_id_idea();
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'keywords' => 'test idea',
      'description' => "...",
      'link' => null,
      'image' => null
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/topic/$this->topicId/idea/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/idea/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/task/$this->taskIdIdea/idea/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'state' => StateIdea::HANDLED,
      'keywords' => 'test idea',
      'description' => "new description",
      'link' => null,
      'image' => null
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/idea/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/idea/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
