<?php
require_once('AuthorizedUserTestCase.php');

use PieLab\GAB\Models\TaskType;
use PieLab\GAB\Models\StateTask;

class DisplayTest extends AuthorizedUserTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstSessionId();
    $this->getFirstTaskIdSelect();
    $this->getFirstTaskIdIdea();
  }

  public function testSetClientState() {
    $state = StateTask::READ_ONLY;
    $res = $this->client->put($this->getAbsoluteApiUrl("/api/task/$this->taskIdIdea/client_application_state/$state/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $state = StateTask::ACTIVE;
    $res = $this->client->put($this->getAbsoluteApiUrl("/api/task/$this->taskIdIdea/client_application_state/$state/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testSetPublicScreen() {
    $this->assertIsString($this->sessionId);
    $res = $this->client->put($this->getAbsoluteApiUrl("/api/session/$this->sessionId/public_screen/$this->taskIdSelect/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    
    $res = $this->client->put($this->getAbsoluteApiUrl("/api/session/$this->sessionId/public_screen/$this->taskIdIdea/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetPublicScreen() {
    $this->assertIsString($this->sessionId);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/session/$this->sessionId/public_screen/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
