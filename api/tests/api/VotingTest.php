<?php
require_once('AuthorizedUserTestCase.php');

class VotingTest extends AuthorizedParticipantTestCase
{
  public function __construct()
  {
    parent::__construct();
    $this->getFirstTaskIdVote();
    $this->getFirstVotingId();
    $this->getFirstIdeaId();
  }

  public function testGetAll() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$this->taskIdVote/votings/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetResult() {
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/task/$this->taskIdVote/voting_result/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testGetById() {
    $this->assertIsString($this->taskIdVote);
    $res = $this->client->get($this->getAbsoluteApiUrl("/api/voting/$this->votingID/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

  public function testWorkflow() {
    $data =  json_encode((object)array(
      'ideaId' => $this->ideaId,
      'rating' => 2,
      'detailRating' => 2.5
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/task/$this->taskIdVote/voting/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $result = $this->toJSON($res->getBody());
    $id = $result->id;

    $data =  json_encode((object)array(
      'id' => $id,
      'ideaId' => $this->ideaId,
      'rating' => 2,
      'detailRating' => 2.7
    ));

    $res = $this->client->put($this->getAbsoluteApiUrl("/api/voting/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/voting/$id/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }
}

?>
