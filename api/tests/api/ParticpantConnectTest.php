<?php
require_once('AuthorizedParticipantTestCase.php');

class ParticpantConnectTest extends AuthorizedTestCase
{
  public function testWorkflow() {
    $data =  json_encode((object)array(
        'sessionKey' => 'ZP4L4QFX',
        'ipHash' => 'xxx'
    ));

    $res = $this->client->post($this->getAbsoluteApiUrl("/api/participant/connect/"), [
        'body' => $data
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
    $browserKey = $this->toJSON($res->getBody())->browserKey;
    $accessToken = $this->toJSON($res->getBody())->accessToken;
    $bearer = "Bearer $accessToken";

    $res = $this->client->get($this->getAbsoluteApiUrl("/api/participant/connect/$browserKey/"));
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());

    $res = $this->client->delete($this->getAbsoluteApiUrl("/api/participant/"), [
        'headers' => ["Authorization" => $bearer]
    ]);
    $this->assertSame($res->getStatusCode(), 200);
    $this->assertIsJSON($res->getBody());
  }

}

?>
